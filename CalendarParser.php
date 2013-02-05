<?php

/**
 * @author Markus Tacker <m@coderbyheart.de>
 *
 * Einfacher Parser für iCal-Dateien
 */
class CalendarParser
{
    /**
     * @var SplFileInfo
     */
    private $file;

    public function __construct(SplFileInfo $file)
    {
        $this->file = $file;
    }

    /**
     * Gibt die Events aus einer iCal-Datei zurück
     *
     * @return stdClass[]
     */
    public function getEvents()
    {
        $fp = fopen($this->file->getPathname(), 'r');
        $currentEvent = null;
        $events = array();
        $data = null;
        $key = null;
        $sort = array();
        while ($line = fgets($fp, 4096)) {
            if (trim($line) === 'BEGIN:VEVENT') {
                $currentEvent = new stdClass();
                continue;
            }
            if (trim($line) === 'END:VEVENT') {
                if ($currentEvent !== null) {
                    $events[] = $currentEvent;
                    $sort[] = $currentEvent->DTSTART;
                }
                $currentEvent = null;
                continue;
            }
            if ($currentEvent === null) continue;
            if (substr($line, 0, 1) === ' ') {
                $currentEvent->$key .= substr($this->unescape($line), 1);
            } else {
                if (strpos($line, ':') === false) continue;

                list($key, $data) = explode(':', $line, 2);
                $currentEvent->$key = $this->unescape($data);

                if (preg_match('/^DT(START|END);[A-Z-]+=.+/', $key, $match)) {
                    $currentEvent->{'DT' . $match[1]} = $data;
                }
            }
        }
        array_multisort($sort, SORT_ASC, $events);
        return $events;
    }

    private function unescape($data)
    {
        return str_replace('\,', ',', trim($data, "\n\r\t\x0B\0"));
    }
}