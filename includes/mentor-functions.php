<?php
/**
 * Common functions for the Ask a Mentor system
 */

/**
 * Get all mentors from the JSON file
 * 
 * @return array Mentors data
 */
function getMentors() {
    $mentorsFile = dirname(__DIR__) . '/assets/data/mentors.json';
    
    if (!file_exists($mentorsFile)) {
        return [];
    }
    
    $mentorsJson = file_get_contents($mentorsFile);
    return json_decode($mentorsJson, true);
}

/**
 * Get a specific mentor by ID
 * 
 * @param int $id Mentor ID
 * @return array|null Mentor data or null if not found
 */
function getMentorById($id) {
    $mentors = getMentors();
    
    foreach ($mentors as $mentor) {
        if ($mentor['id'] == $id) {
            return $mentor;
        }
    }
    
    return null;
}

/**
 * Get all bookings from the JSON file
 * 
 * @return array Bookings data
 */
function getBookings() {
    $bookingsFile = dirname(__DIR__) . '/assets/data/bookings.json';
    
    if (!file_exists($bookingsFile)) {
        return [];
    }
    
    $bookingsJson = file_get_contents($bookingsFile);
    return json_decode($bookingsJson, true);
}

/**
 * Save a new booking to the JSON file
 * 
 * @param array $booking Booking data
 * @return bool Success status
 */
function saveBooking($booking) {
    $bookingsFile = dirname(__DIR__) . '/assets/data/bookings.json';
    $bookings = getBookings();
    
    // Generate a unique ID for the booking
    $booking['id'] = time() . rand(1000, 9999);
    $booking['created_at'] = date('Y-m-d H:i:s');
    
    $bookings[] = $booking;
    
    return file_put_contents($bookingsFile, json_encode($bookings, JSON_PRETTY_PRINT));
}

/**
 * Check if a time slot is available for a mentor
 * 
 * @param int $mentorId Mentor ID
 * @param string $date Date in Y-m-d format
 * @param string $time Time in H:i format
 * @return bool True if available, false if booked
 */
function isTimeSlotAvailable($mentorId, $date, $time) {
    $bookings = getBookings();
    
    foreach ($bookings as $booking) {
        if ($booking['mentor_id'] == $mentorId && 
            $booking['date'] == $date && 
            $booking['time'] == $time) {
            return false;
        }
    }
    
    return true;
}

/**
 * Get available time slots for a mentor on a specific date
 * 
 * @param int $mentorId Mentor ID
 * @param string $date Date in Y-m-d format
 * @return array Available time slots
 */
function getAvailableTimeSlots($mentorId, $date) {
    // Default available time slots (10:00 AM - 6:00 PM, 30-minute intervals)
    $defaultSlots = [
        '10:00', '10:30', '11:00', '11:30', '12:00', '12:30',
        '13:00', '13:30', '14:00', '14:30', '15:00', '15:30',
        '16:00', '16:30', '17:00', '17:30', '18:00'
    ];
    
    $availableSlots = [];
    
    foreach ($defaultSlots as $slot) {
        if (isTimeSlotAvailable($mentorId, $date, $slot)) {
            $availableSlots[] = $slot;
        }
    }
    
    return $availableSlots;
}

/**
 * Validate booking form data
 * 
 * @param array $data Form data
 * @return array Errors (empty if valid)
 */
function validateBookingForm($data) {
    $errors = [];
    
    if (empty($data['full_name'])) {
        $errors['full_name'] = 'Full name is required';
    }
    
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Valid email is required';
    }
    
    if (empty($data['message'])) {
        $errors['message'] = 'Message is required';
    }
    
    if (empty($data['date'])) {
        $errors['date'] = 'Date is required';
    }
    
    if (empty($data['time'])) {
        $errors['time'] = 'Time slot is required';
    }
    
    if (!empty($data['date']) && !empty($data['time'])) {
        // Check if the time slot is still available
        if (!isTimeSlotAvailable($data['mentor_id'], $data['date'], $data['time'])) {
            $errors['time'] = 'This time slot is no longer available';
        }
    }
    
    return $errors;
}