<?php
namespace App\Repository;

class AuditLogRepository extends BaseRepository {
    protected string $table = 'audit_logs';
    protected bool $insertOnly = true;
}
