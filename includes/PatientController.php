<?php

class PatientController {
    public function login($uhid, $name) {
        try {
            $patient = $this->db->fetch(
                "SELECT * FROM patients WHERE UHID = ? AND name = ?",
                [$uhid, $name]
            );

            if ($patient) {
                $_SESSION['patient_id'] = $patient['id'];
                $_SESSION['patient_uhid'] = $patient['UHID'];
                $_SESSION['patient_name'] = $patient['name'];
                return true;
            }
            return false;
        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return false;
        }
    }
} 