<?php
/**
 * Bulk Actions Handler
 * 
 * Provides functionality for bulk operations on admin list pages.
 */

class BulkActions {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Process bulk action request
     */
    public function process($type, $action, $ids) {
        if (empty($ids) || !is_array($ids)) {
            return ['success' => false, 'message' => 'No items selected'];
        }
        
        $ids = array_map('intval', $ids);
        
        switch ($type) {
            case 'rooms':
                return $this->bulkRooms($action, $ids);
            case 'bookings':
                return $this->bulkBookings($action, $ids);
            case 'guests':
                return $this->bulkGuests($action, $ids);
            default:
                return ['success' => false, 'message' => 'Invalid type'];
        }
    }
    
    /**
     * Bulk room actions
     */
    private function bulkRooms($action, $ids) {
        switch ($action) {
            case 'delete':
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $this->pdo->prepare("DELETE FROM rooms WHERE id IN ($placeholders)");
                $stmt->execute($ids);
                return [
                    'success' => true,
                    'message' => $stmt->rowCount() . ' rooms deleted successfully',
                    'count' => $stmt->rowCount()
                ];
                
            case 'set_available':
                $stmt = $this->pdo->prepare("UPDATE rooms SET status = 'available' WHERE id IN ($placeholders)");
                $stmt->execute($ids);
                return [
                    'success' => true,
                    'message' => $stmt->rowCount() . ' rooms set to available',
                    'count' => $stmt->rowCount()
                ];
                
            case 'set_maintenance':
                $stmt = $this->pdo->prepare("UPDATE rooms SET status = 'maintenance' WHERE id IN ($placeholders)");
                $stmt->execute($ids);
                return [
                    'success' => true,
                    'message' => $stmt->rowCount() . ' rooms set to maintenance',
                    'count' => $stmt->rowCount()
                ];
                
            default:
                return ['success' => false, 'message' => 'Invalid action'];
        }
    }
    
    /**
     * Bulk booking actions
     */
    private function bulkBookings($action, $ids) {
        switch ($action) {
            case 'delete':
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $this->pdo->prepare("DELETE FROM bookings WHERE id IN ($placeholders)");
                $stmt->execute($ids);
                return [
                    'success' => true,
                    'message' => $stmt->rowCount() . ' bookings deleted successfully',
                    'count' => $stmt->rowCount()
                ];
                
            case 'confirm':
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $this->pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id IN ($placeholders) AND status = 'pending'");
                $stmt->execute($ids);
                return [
                    'success' => true,
                    'message' => $stmt->rowCount() . ' bookings confirmed',
                    'count' => $stmt->rowCount()
                ];
                
            case 'cancel':
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmt = $this->pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id IN ($placeholders) AND status IN ('pending', 'confirmed')");
                $stmt->execute($ids);
                return [
                    'success' => true,
                    'message' => $stmt->rowCount() . ' bookings cancelled',
                    'count' => $stmt->rowCount()
                ];
                
            default:
                return ['success' => false, 'message' => 'Invalid action'];
        }
    }
    
    /**
     * Bulk guest actions
     */
    private function bulkGuests($action, $ids) {
        switch ($action) {
            case 'export':
                return $this->exportGuests($ids);
                
            default:
                return ['success' => false, 'message' => 'Invalid action'];
        }
    }
    
    /**
     * Export guests to CSV
     */
    private function exportGuests($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->pdo->prepare(
            "SELECT u.full_name, u.email, u.phone, COUNT(b.id) as booking_count
             FROM users u
             LEFT JOIN bookings b ON b.user_id = u.id
             WHERE u.id IN ($placeholders)
             GROUP BY u.id
             ORDER BY u.full_name"
        );
        $stmt->execute($ids);
        $guests = $stmt->fetchAll();
        
        $filename = 'guests_export_' . date('Y-m-d_H-i-s') . '.csv';
        $filepath = sys_get_temp_dir() . $filename;
        
        $handle = fopen($filepath, 'w');
        if ($handle === false) {
            return ['success' => false, 'message' => 'Failed to create export file'];
        }
        
        fputcsv($handle, ['Name', 'Email', 'Phone', 'Booking Count']);
        
        foreach ($guests as $guest) {
            fputcsv($handle, [
                $guest['full_name'],
                $guest['email'],
                $guest['phone'] ?? '',
                $guest['booking_count']
            ]);
        }
        
        fclose($handle);
        
        return [
            'success' => true,
            'message' => 'Guests exported successfully',
            'filepath' => $filepath,
            'filename' => $filename
        ];
    }
    
    /**
     * Export data to CSV format
     */
    public function exportToCsv($data, $filename, $headers = []) {
        $filepath = sys_get_temp_dir() . $filename;
        $handle = fopen($filepath, 'w');
        
        if ($handle === false) {
            return ['success' => false, 'message' => 'Failed to create export file'];
        }
        
        if (!empty($headers)) {
            fputcsv($handle, $headers);
        }
        
        foreach ($data as $row) {
            fputcsv($handle, $row);
        }
        
        fclose($handle);
        
        return [
            'success' => true,
            'filepath' => $filepath,
            'filename' => $filename
        ];
    }
}
