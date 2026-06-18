<?php
namespace App\Controllers;

use App\App\Auth;
use App\Database\Connection;
use App\Utils\Validator;
use App\Utils\Security;

class AdminController extends BaseController {

    // ─── Dashboard ────────────────────────────────────────────────────────────

    public function dashboard(): void {
        $this->requireRole(['admin']);
        $db = Connection::getConnection();

        $stats = [
            'total_users'           => $db->query("SELECT COUNT(*) FROM users WHERE deleted_at IS NULL")->fetchColumn(),
            'today_appointments'    => $db->query("SELECT COUNT(*) FROM appointments WHERE DATE(created_at) = CURDATE()")->fetchColumn(),
            'pending_prescriptions' => $db->query("SELECT COUNT(*) FROM prescriptions WHERE status = 'pending'")->fetchColumn(),
            'low_stock_count'       => $db->query("SELECT COUNT(*) FROM medicines WHERE stock < minimum_stock AND deleted_at IS NULL")->fetchColumn(),
        ];

        $this->view('admin.dashboard', ['stats' => $stats, 'user' => Auth::user()]);
    }

    // ─── User Management ──────────────────────────────────────────────────────

    public function users(): void {
        $this->requireRole(['admin']);
        $db    = Connection::getConnection();
        $users = $db->query("SELECT id, public_id, name, email, role, is_active, created_at FROM users WHERE deleted_at IS NULL ORDER BY created_at DESC")->fetchAll();

        $this->view('admin.users', ['users' => $users, 'user' => Auth::user()]);
    }

    public function createUserForm(): void {
        $this->requireRole(['admin']);
        $this->view('admin.user_create', ['user' => Auth::user()]);
    }

    public function createUser(): void {
        $this->requireRole(['admin']);
        Security::verifyCsrfOrFail();

        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = $_POST['role'] ?? '';
        $isActive = (int)($_POST['is_active'] ?? 1);

        $allowed = ['admin', 'doctor', 'receptionist', 'apoteker', 'patient'];

        if (empty($name) || empty($email) || empty($password) || !in_array($role, $allowed)) {
            $this->redirect('admin', ['action' => 'create-user', 'error' => urlencode('Semua kolom wajib diisi dengan benar.')]);
        }
        if (strlen($password) < 8) {
            $this->redirect('admin', ['action' => 'create-user', 'error' => urlencode('Password minimal 8 karakter.')]);
        }

        $db   = Connection::getConnection();
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $this->redirect('admin', ['action' => 'create-user', 'error' => urlencode('Email sudah terdaftar.')]);
        }

        // Patient NIK & Info Validation
        $nik = '';
        if ($role === 'patient') {
            $nik = trim($_POST['nik'] ?? '');
            $dob = trim($_POST['date_of_birth'] ?? '');
            $gender = trim($_POST['gender'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');

            if (empty($nik) || strlen($nik) !== 16 || !ctype_digit($nik)) {
                $this->redirect('admin', ['action' => 'create-user', 'error' => urlencode('NIK Pasien wajib berupa 16 digit angka.')]);
                return;
            }
            if (empty($dob) || empty($gender) || empty($phone) || empty($address)) {
                $this->redirect('admin', ['action' => 'create-user', 'error' => urlencode('Semua data medis pasien wajib diisi.')]);
                return;
            }

            $stmtNik = $db->prepare("SELECT user_id FROM patients WHERE nik = ? LIMIT 1");
            $stmtNik->execute([$nik]);
            if ($stmtNik->fetch()) {
                $this->redirect('admin', ['action' => 'create-user', 'error' => urlencode('NIK Pasien sudah terdaftar di sistem.')]);
                return;
            }
        }

        $hash      = Security::hashPassword($password);
        $publicId  = 'USR-' . strtoupper(substr(bin2hex(random_bytes(4)), 0, 8));

        $db->beginTransaction();
        try {
            $db->prepare("
                INSERT INTO users (public_id, name, email, password_hash, role, is_active, force_password_change, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, 1, NOW(), NOW())
            ")->execute([$publicId, $name, $email, $hash, $role, $isActive]);
            
            $userId = (int)$db->lastInsertId();

            if ($role === 'patient') {
                $nikEncrypted = Security::encryptNik($nik);
                $nikHash = Security::hashNik($nik);

                $db->prepare("
                    INSERT INTO patients (user_id, nik, nik_encrypted, nik_hash, date_of_birth, gender, phone, address)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ")->execute([$userId, $nik, $nikEncrypted, $nikHash, $dob, $gender, $phone, $address]);
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            $this->redirect('admin', ['action' => 'create-user', 'error' => urlencode('Gagal membuat pengguna: ' . $e->getMessage())]);
            return;
        }

        Auth::logAudit('admin.create_user', 'users');
        $this->redirect('admin', ['action' => 'users', 'success' => urlencode("Pengguna '{$name}' berhasil dibuat.")]);
    }

    public function editUserForm(): void {
        $this->requireRole(['admin']);
        $targetId = $this->inputInt('id');
        $db = Connection::getConnection();
        $stmt = $db->prepare("SELECT id, name, email, role, is_active FROM users WHERE id = ? AND deleted_at IS NULL LIMIT 1");
        $stmt->execute([$targetId]);
        $target = $stmt->fetch();

        if (!$target) {
            $this->redirect('admin', ['action' => 'users', 'error' => urlencode('Pengguna tidak ditemukan.')]);
        }

        $patient = null;
        if ($target['role'] === 'patient') {
            $stmtPatient = $db->prepare("SELECT * FROM patients WHERE user_id = ? LIMIT 1");
            $stmtPatient->execute([$targetId]);
            $patient = $stmtPatient->fetch() ?: null;
        }

        $this->view('admin.user_edit', ['target' => $target, 'patient' => $patient, 'user' => Auth::user()]);
    }

    public function editUser(): void {
        $this->requireRole(['admin']);
        Security::verifyCsrfOrFail();

        $targetId = (int)($_POST['user_id'] ?? 0);
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $role     = $_POST['role'] ?? '';
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $password = $_POST['password'] ?? '';

        $allowed = ['admin', 'doctor', 'receptionist', 'apoteker', 'patient'];

        if (empty($name) || empty($email) || !in_array($role, $allowed)) {
            $this->redirect('admin', ['action' => 'edit-user', 'id' => $targetId, 'error' => urlencode('Data tidak valid.')]);
        }

        $db = Connection::getConnection();

        // Check email uniqueness (exclude current user)
        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
        $stmt->execute([$email, $targetId]);
        if ($stmt->fetch()) {
            $this->redirect('admin', ['action' => 'edit-user', 'id' => $targetId, 'error' => urlencode('Email sudah digunakan pengguna lain.')]);
        }

        // Patient NIK & Info Validation
        $nik = '';
        if ($role === 'patient') {
            $nik = trim($_POST['nik'] ?? '');
            $dob = trim($_POST['date_of_birth'] ?? '');
            $gender = trim($_POST['gender'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $address = trim($_POST['address'] ?? '');

            if (empty($nik) || strlen($nik) !== 16 || !ctype_digit($nik)) {
                $this->redirect('admin', ['action' => 'edit-user', 'id' => $targetId, 'error' => urlencode('NIK Pasien wajib berupa 16 digit angka.')]);
                return;
            }
            if (empty($dob) || empty($gender) || empty($phone) || empty($address)) {
                $this->redirect('admin', ['action' => 'edit-user', 'id' => $targetId, 'error' => urlencode('Semua data medis pasien wajib diisi.')]);
                return;
            }

            $stmtNik = $db->prepare("SELECT user_id FROM patients WHERE nik = ? AND user_id != ? LIMIT 1");
            $stmtNik->execute([$nik, $targetId]);
            if ($stmtNik->fetch()) {
                $this->redirect('admin', ['action' => 'edit-user', 'id' => $targetId, 'error' => urlencode('NIK Pasien sudah digunakan oleh pasien lain.')]);
                return;
            }
        }

        $db->beginTransaction();
        try {
            if (!empty($password)) {
                if (strlen($password) < 8) {
                    $this->redirect('admin', ['action' => 'edit-user', 'id' => $targetId, 'error' => urlencode('Password minimal 8 karakter.')]);
                    return;
                }
                $hash = Security::hashPassword($password);
                $db->prepare("UPDATE users SET name=?, email=?, role=?, is_active=?, password_hash=?, force_password_change=1, updated_at=NOW() WHERE id=?")
                   ->execute([$name, $email, $role, $isActive, $hash, $targetId]);
            } else {
                $db->prepare("UPDATE users SET name=?, email=?, role=?, is_active=?, updated_at=NOW() WHERE id=?")
                   ->execute([$name, $email, $role, $isActive, $targetId]);
            }

            if ($role === 'patient') {
                $nikEncrypted = Security::encryptNik($nik);
                $nikHash = Security::hashNik($nik);

                $db->prepare("
                    INSERT INTO patients (user_id, nik, nik_encrypted, nik_hash, date_of_birth, gender, phone, address)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE 
                        nik = VALUES(nik),
                        nik_encrypted = VALUES(nik_encrypted),
                        nik_hash = VALUES(nik_hash),
                        date_of_birth = VALUES(date_of_birth),
                        gender = VALUES(gender),
                        phone = VALUES(phone),
                        address = VALUES(address)
                ")->execute([$targetId, $nik, $nikEncrypted, $nikHash, $dob, $gender, $phone, $address]);
            }

            $db->commit();
        } catch (\Throwable $e) {
            $db->rollBack();
            $this->redirect('admin', ['action' => 'edit-user', 'id' => $targetId, 'error' => urlencode('Gagal memperbarui pengguna: ' . $e->getMessage())]);
            return;
        }

        Auth::logAudit('admin.edit_user', 'users', $targetId);
        $this->redirect('admin', ['action' => 'users', 'success' => urlencode("Data pengguna berhasil diperbarui.")]);
    }

    public function deleteUser(): void {
        $this->requireRole(['admin']);
        Security::verifyCsrfOrFail();

        $targetId = (int)($_POST['user_id'] ?? 0);

        // Prevent self-deletion
        if ($targetId === Auth::id()) {
            $this->redirect('admin', ['action' => 'users', 'error' => urlencode('Anda tidak dapat menghapus akun sendiri.')]);
        }

        $db = Connection::getConnection();
        $db->prepare("UPDATE users SET deleted_at = NOW() WHERE id = ? AND deleted_at IS NULL")
           ->execute([$targetId]);

        Auth::logAudit('admin.delete_user', 'users', $targetId);
        $this->redirect('admin', ['action' => 'users', 'success' => urlencode('Pengguna berhasil dihapus dari sistem.')]);
    }

    public function toggleUserActive(): void {
        $this->requireRole(['admin']);
        Security::verifyCsrfOrFail();

        $targetId = $this->inputInt('user_id');
        $db = Connection::getConnection();
        $stmt = $db->prepare("SELECT is_active FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$targetId]);
        $target = $stmt->fetch();

        if (!$target) {
            $this->redirect('admin', ['action' => 'users', 'error' => urlencode('User tidak ditemukan.')]);
        }

        $newStatus = $target['is_active'] ? 0 : 1;
        $db->prepare("UPDATE users SET is_active = ? WHERE id = ?")->execute([$newStatus, $targetId]);
        Auth::logAudit('admin.toggle_user', 'users', $targetId);

        $this->redirect('admin', ['action' => 'users', 'success' => urlencode('Status user berhasil diubah.')]);
    }

    // ─── System Settings ──────────────────────────────────────────────────────

    public function settings(): void {
        $this->requireRole(['admin']);
        $db = Connection::getConnection();
        $settings = $db->query("SELECT setting_key, setting_value, description FROM system_settings ORDER BY setting_key")->fetchAll();

        $this->view('admin.settings', ['settings' => $settings, 'user' => Auth::user()]);
    }

    public function saveSetting(): void {
        $this->requireRole(['admin']);
        Security::verifyCsrfOrFail();

        $key   = Security::sanitizeString($_POST['setting_key'] ?? '');
        $value = Security::sanitizeString($_POST['setting_value'] ?? '');

        if (empty($key)) {
            $this->redirect('admin', ['action' => 'settings', 'error' => urlencode('Key tidak boleh kosong.')]);
        }

        $db = Connection::getConnection();
        $db->prepare("
            INSERT INTO system_settings (setting_key, setting_value, updated_at) VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value), updated_at = NOW()
        ")->execute([$key, $value]);

        Auth::logAudit('admin.save_setting', 'system_settings');
        $this->redirect('admin', ['action' => 'settings', 'success' => urlencode("Setting '{$key}' berhasil disimpan.")]);
    }

    // ─── Audit Logs ───────────────────────────────────────────────────────────

    public function auditLogs(): void {
        $this->requireRole(['admin']);
        $db   = Connection::getConnection();
        $logs = $db->query("
            SELECT al.id, al.action, al.entity_table, al.entity_id, al.ip_address, al.created_at,
                   u.name AS user_name
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.id
            ORDER BY al.created_at DESC
            LIMIT 200
        ")->fetchAll();

        $this->view('admin.audit_logs', ['logs' => $logs, 'user' => Auth::user()]);
    }
}
