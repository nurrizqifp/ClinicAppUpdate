<?php $pageTitle = 'Kelola Pengguna'; include BASE_PATH.'/src/Views/layout/header.php'; ?>

<div class="page-header">
    <div class="page-header-text">
        <h1>Kelola Pengguna</h1>
        <p>Manajemen akun staf, dokter, resepsionis, apoteker, dan hak akses sistem</p>
    </div>
    <a href="<?= $baseUrl ?>/admin?action=create-user" class="btn btn-primary">
        <i data-lucide="user-plus" style="width:16px;height:16px;"></i>
        Tambah Pengguna
    </a>
</div>

<div class="card">
    <div class="card-header">
        <h3 class="card-title">
            <i data-lucide="users" style="width:18px;height:18px;"></i>
            Daftar Pengguna Terdaftar
        </h3>
        <span style="font-size:.78rem;color:var(--text-muted);"><?= count($users) ?> pengguna</span>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Nama Pengguna</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Terdaftar</th>
                    <th style="text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $u):
                $roleClass = match($u['role']) {
                    'admin'        => 'badge-admin',
                    'doctor'       => 'badge-doctor',
                    'patient'      => 'badge-patient',
                    'receptionist' => 'badge-receptionist',
                    'apoteker'     => 'badge-apoteker',
                    default        => 'badge-normal'
                };
            ?>
                <tr>
                    <td>
                        <div style="font-weight:600;color:var(--text-primary);font-size:.9rem;">
                            <?= htmlspecialchars($u['name']) ?>
                        </div>
                    </td>
                    <td>
                        <span style="color:var(--text-secondary);font-size:.85rem;">
                            <?= htmlspecialchars($u['email']) ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge <?= $roleClass ?>">
                            <?= htmlspecialchars($u['role']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($u['is_active']): ?>
                            <span class="badge badge-active">Aktif</span>
                        <?php else: ?>
                            <span class="badge badge-inactive">Nonaktif</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span style="font-family:'Fira Code',monospace;font-size:.8rem;color:var(--text-muted);">
                            <?= date('d/m/Y', strtotime($u['created_at'])) ?>
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:.375rem;justify-content:flex-end;align-items:center;">
                            <!-- Edit -->
                            <a href="<?= $baseUrl ?>/admin?action=edit-user&id=<?= $u['id'] ?>"
                               class="btn btn-outline btn-sm"
                               title="Edit pengguna">
                                <i data-lucide="pencil" style="width:12px;height:12px;"></i>
                                Edit
                            </a>

                            <!-- Toggle Aktif -->
                            <form method="POST" action="<?= $baseUrl ?>/admin?action=toggle-user" style="display:inline-block;">
                                <?= $csrfField ?>
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <?php if ($u['is_active']): ?>
                                    <button type="submit" class="btn btn-outline btn-sm"
                                            style="color:var(--amber);border-color:rgba(234,179,8,.3);"
                                            title="Nonaktifkan akun">
                                        <i data-lucide="pause-circle" style="width:12px;height:12px;"></i>
                                        Nonaktif
                                    </button>
                                <?php else: ?>
                                    <button type="submit" class="btn btn-outline btn-sm"
                                            style="color:var(--emerald);border-color:rgba(5,150,105,.3);"
                                            title="Aktifkan akun">
                                        <i data-lucide="play-circle" style="width:12px;height:12px;"></i>
                                        Aktif
                                    </button>
                                <?php endif; ?>
                            </form>

                            <!-- Delete -->
                            <form method="POST" action="<?= $baseUrl ?>/admin?action=delete-user"
                                  style="display:inline-block;"
                                  onsubmit="return confirm('Hapus pengguna <?= htmlspecialchars(addslashes($u['name'])) ?>? Tindakan ini tidak dapat dibatalkan.')">
                                <?= $csrfField ?>
                                <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                <button type="submit" class="btn btn-outline btn-sm"
                                        style="color:var(--red);border-color:rgba(239,68,68,.2);"
                                        title="Hapus pengguna">
                                    <i data-lucide="trash-2" style="width:12px;height:12px;"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($users)): ?>
                <tr>
                    <td colspan="6">
                        <div class="empty-state">
                            <i data-lucide="users" style="width:40px;height:40px;"></i>
                            <h3>Belum ada pengguna</h3>
                            <p>Klik "Tambah Pengguna" untuk menambahkan akun baru.</p>
                        </div>
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include BASE_PATH.'/src/Views/layout/footer.php'; ?>
