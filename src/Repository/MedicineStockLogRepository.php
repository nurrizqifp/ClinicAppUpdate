<?php
namespace App\Repository;

class MedicineStockLogRepository extends BaseRepository {
    protected string $table = 'medicine_stock_logs';
    protected bool $insertOnly = true;
}
