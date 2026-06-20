<?php

namespace App\Services;

use Google\Client;
use Google\Service\Calendar;
use Google\Service\Calendar\Event;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Calendar\ConferenceData;
use Google\Service\Calendar\CreateConferenceRequest;
use Google\Service\Calendar\ConferenceSolutionKey;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class GoogleMeetService
{
    protected $client;
    protected $calendar;

    public function __construct()
    {
        $this->client = new Client();
        $this->client->setAuthConfig(json_decode(env('GOOGLE_SERVICE_ACCOUNT_JSON'), true));
        $this->client->setScopes([Calendar::CALENDAR]);
        $this->calendar = new Calendar($this->client);
    }

    /**
     * Create a Google Meet link for an appointment
     */
    public function createMeetLink($appointmentData)
    {
        try {
            $event = new Event([
                'summary' => $appointmentData['summary'] ?? 'Mentorship Session',
                'description' => $appointmentData['description'] ?? 'Mentorship session between mentor and mentee',
                'start' => new EventDateTime([
                    'dateTime' => $appointmentData['start_time'],
                    'timeZone' => 'Asia/Kuala_Lumpur',
                ]),
                'end' => new EventDateTime([
                    'dateTime' => $appointmentData['end_time'],
                    'timeZone' => 'Asia/Kuala_Lumpur',
                ]),
                'attendees' => $appointmentData['attendees'] ?? [],
                'conferenceData' => new ConferenceData([
                    'createRequest' => new CreateConferenceRequest([
                        'requestId' => Str::uuid()->toString(),
                        'conferenceSolutionKey' => new ConferenceSolutionKey([
                            'type' => 'hangoutsMeet'
                        ])
                    ])
                ]),
            ]);

            $calendarId = 'primary';
            $createdEvent = $this->calendar->events->insert($calendarId, $event, [
                'conferenceDataVersion' => 1,
                'sendUpdates' => 'all'
            ]);

            if ($createdEvent->getConferenceData()) {
                $conferenceData = $createdEvent->getConferenceData();
                $entryPoints = $conferenceData->getEntryPoints();
                
                foreach ($entryPoints as $entryPoint) {
                    if ($entryPoint->getEntryPointType() === 'video') {
                        return $entryPoint->getUri();
                    }
                }
            }

            return null;
        } catch (\Exception $e) {
            Log::error('Google Meet creation error: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Generate a simple Google Meet link (fallback without API)
     */
    public function generateSimpleMeetLink()
    {
        // Always use a valid Google Meet landing URL as fallback.
        return 'https://meet.google.com/new';
    }

    private function generateMeetCode()
    {
        $segments = [];
        for ($i = 0; $i < 3; $i++) {
            $segments[] = Str::random(3);
        }
        return implode('-', $segments);
    }
}
