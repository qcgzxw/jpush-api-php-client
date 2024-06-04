<?php

namespace JPush;

use InvalidArgumentException;

class SchedulePayload extends Payload {

    /**
     * SchedulePayload constructor.
     * @param $client JPush
     */
    public function __construct($client) {
        parent::__construct($client);
    }

    public function createSingleSchedule($name, $push_payload, $trigger) {
        $this->validateScheduleParams($name, $push_payload, $trigger);

        $payload = [
            'name' => $name,
            'enabled' => true,
            'trigger' => ['single' => $trigger],
            'push' => $push_payload
        ];

        $url = $this->client->makeURL('schedule');
        return $this->post($url, $payload);
    }

    public function createPeriodicalSchedule($name, $push_payload, $trigger) {
        $this->validateScheduleParams($name, $push_payload, $trigger);

        $payload = [
            'name' => $name,
            'enabled' => true,
            'trigger' => ['periodical' => $trigger],
            'push' => $push_payload
        ];

        $url = $this->client->makeURL('schedule');
        return $this->post($url, $payload);
    }

    public function updateSingleSchedule($schedule_id, $name = null, $enabled = null, $push_payload = null, $trigger = null) {
        if (!is_string($schedule_id)) {
            throw new InvalidArgumentException('Invalid schedule id');
        }
        $payload = $this->buildUpdatePayload($name, $enabled, $push_payload, $trigger, 'single');

        $url = $this->client->makeURL('schedule') . '/' . $schedule_id;
        return $this->put($url, $payload);
    }

    public function updatePeriodicalSchedule($schedule_id, $name = null, $enabled = null, $push_payload = null, $trigger = null) {
        if (!is_string($schedule_id)) {
            throw new InvalidArgumentException('Invalid schedule id');
        }
        $payload = $this->buildUpdatePayload($name, $enabled, $push_payload, $trigger, 'periodical');

        $url = $this->client->makeURL('schedule') . '/' . $schedule_id;
        return $this->put($url, $payload);
    }

    public function getSchedules($page = 1) {
        $url = $this->client->makeURL('schedule');
        $params = ['page' => is_int($page) ? $page : 1];
        return $this->get($url, $params);
    }

    public function getSchedule($schedule_id) {
        if (!is_string($schedule_id)) {
            throw new InvalidArgumentException('Invalid schedule id');
        }
        $url = $this->client->makeURL('schedule') . '/' . $schedule_id;
        return $this->get($url);
    }

    public function deleteSchedule($schedule_id) {
        if (!is_string($schedule_id)) {
            throw new InvalidArgumentException('Invalid schedule id');
        }
        $url = $this->client->makeURL('schedule') . '/' . $schedule_id;
        return $this->delete($url);
    }

    public function getMsgIds($schedule_id) {
        if (!is_string($schedule_id)) {
            throw new InvalidArgumentException('Invalid schedule id');
        }
        $url = $this->client->makeURL('schedule') . '/' . $schedule_id . '/msg_ids';
        return $this->get($url);
    }

    private function validateScheduleParams($name, $push_payload, $trigger) {
        if (!is_string($name)) {
            throw new InvalidArgumentException('Invalid schedule name');
        }
        if (!is_array($push_payload)) {
            throw new InvalidArgumentException('Invalid schedule push payload');
        }
        if (!is_array($trigger)) {
            throw new InvalidArgumentException('Invalid schedule trigger');
        }
    }

    private function buildUpdatePayload($name, $enabled, $push_payload, $trigger, $triggerType) {
        $payload = [];

        if (!is_null($name)) {
            if (!is_string($name)) {
                throw new InvalidArgumentException('Invalid schedule name');
            }
            $payload['name'] = $name;
        }

        if (!is_null($enabled)) {
            if (!is_bool($enabled)) {
                throw new InvalidArgumentException('Invalid schedule enable');
            }
            $payload['enabled'] = $enabled;
        }

        if (!is_null($push_payload)) {
            if (!is_array($push_payload)) {
                throw new InvalidArgumentException('Invalid schedule push payload');
            }
            $payload['push'] = $push_payload;
        }

        if (!is_null($trigger)) {
            if (!is_array($trigger)) {
                throw new InvalidArgumentException('Invalid schedule trigger');
            }
            $payload['trigger'] = [$triggerType => $trigger];
        }

        if (empty($payload)) {
            throw new InvalidArgumentException('Invalid schedule, name, enabled, trigger, push cannot all be null');
        }

        return $payload;
    }
}
