<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Unit Tests for Event
 *
 * @package    paygw_razorpay
 * @copyright  2025 Santosh N.(santosh.nag2217@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace paygw_razorpay\event;

/**
 * Unit Test class for Event
 *
 * @package    paygw_razorpay
 * @copyright  2025 Santosh N.(santosh.nag2217@gmail.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \paygw_razorpay\event\payment_created
 */
final class events_test extends \advanced_testcase {
    /**
     * Tests payment_created event
     * @return void
     * @throws \coding_exception
     * @covers \paygw_razorpay\event\payment_created
     */
    public function test_payment_created(): void {
        $this->resetAfterTest();
        $course = $this->getDataGenerator()->create_course();
        $coursecontext = \context_course::instance($course->id);

        $eventinfo = [
            'contextid' => $coursecontext->id,
            'userid' => 4,
            'objectid' => 8,
            'other' => [
                'currency' => 'INR',
                'amount' => 1000,
            ],
        ];

        $sink = $this->redirectEvents();
        $event = payment_created::create($eventinfo);
        $event->trigger();
        $result = $sink->get_events();
        $event = reset($result);
        $sink->close();
        $this->assertEquals(4, $event->userid);
        $this->assertEquals($coursecontext->id, $event->contextid);
        $this->assertEquals('INR', $event->other['currency']);
        $this->assertEquals(get_string('eventpaymentdone', 'paygw_razorpay'), $event->get_name());
    }
}
