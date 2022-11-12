<?php

namespace UnipvLectures\Utils;

use UnipvLectures\Enums\Day;
use UnipvLectures\Enums\Time;

class Helper {

    const LECTURERS_TIME_SLOTS = [
        '09:00-10:00',
        '10:00-11:00',
        '11:00-12:00',
        '12:00-13:00',
        '13:00-14:00',
        '14:00-15:00',
        '15:00-16:00',
        '16:00-17:00',
        '17:00-18:00',
        'ONLINE',
    ];

    public static function toTimeSlot(Time $time): string | null {

        switch ($time){
            case Time::_9:
                return self::LECTURERS_TIME_SLOTS[0];
            case Time::_10:
                return self::LECTURERS_TIME_SLOTS[1];
            case Time::_11:
                return self::LECTURERS_TIME_SLOTS[2];
            case Time::_12:
                return self::LECTURERS_TIME_SLOTS[3];
            case Time::_14:
                return self::LECTURERS_TIME_SLOTS[5];
            case Time::_15:
                return self::LECTURERS_TIME_SLOTS[6];
            case Time::_16:
                return self::LECTURERS_TIME_SLOTS[7];
            case Time::_17:
                return self::LECTURERS_TIME_SLOTS[8];
        }

        return null;
    }

	public static function toDayOrderNumber( $day ): int {

        return match ( $day ) {
            Day::Monday->name => 0,
            Day::Tuesday->name => 1,
            Day::Wednesday->name => 2,
            Day::Thursday->name => 3,
            Day::Friday->name => 4,
            default => - 1,
        };

    }

}
