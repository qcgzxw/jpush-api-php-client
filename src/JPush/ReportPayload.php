<?php

namespace JPush;

use InvalidArgumentException;

class ReportPayload extends Payload {
    private static $EFFECTIVE_TIME_UNIT = ['HOUR', 'DAY', 'MONTH'];

    /**
     * ReportPayload constructor.
     * @param $client JPush
     */
    public function __construct($client)
    {
        parent::__construct($client);
    }

    public function getReceived($msgIds) {
        $params = $this->buildMsgIdsParams($msgIds);
        $url = $this->client->makeURL('report') . 'received';
        return $this->get($url, $params);
    }

    public function getReceivedDetail($msgIds) {
        $params = $this->buildMsgIdsParams($msgIds);
        $url = $this->client->makeURL('report') . 'received/detail';
        return $this->get($url, $params);
    }

    public function getMessageStatus($msgId, $rids, $data = null) {
        $url = $this->client->makeURL('report') . 'status/message';
        $registrationIds = is_array($rids) ? $rids : [$rids];
        $body = [
            'msg_id' => $msgId,
            'registration_ids' => $registrationIds
        ];
        if (!is_null($data)) {
            $body['data'] = $data;
        }
        return $this->post($url, $body);
    }

    public function getMessages($msgIds) {
        $params = $this->buildMsgIdsParams($msgIds);
        $url = $this->client->makeURL('report') . 'messages';
        return $this->get($url, $params);
    }

    /*
     消息统计详情（VIP 专属接口，新）
     https://docs.jiguang.cn/jpush/server/push/rest_api_v3_report/#vip_1
    */
    public function getMessagesDetail($msgIds) {
        $params = $this->buildMsgIdsParams($msgIds);
        $url = $this->client->makeURL('report') . 'messages/detail';
        return $this->get($url, $params);
    }

    public function getUsers($time_unit, $start, $duration) {
        $time_unit = strtoupper($time_unit);
        if (!in_array($time_unit, self::$EFFECTIVE_TIME_UNIT)) {
            throw new InvalidArgumentException('Invalid time unit');
        }

        $params = [
            'time_unit' => $time_unit,
            'start' => $start,
            'duration' => $duration
        ];
        $url = $this->client->makeURL('report') . 'users';
        return $this->get($url, $params);
    }

    private function buildMsgIdsParams($msgIds) {
        if (is_array($msgIds) && !empty($msgIds)) {
            $msgIdsStr = implode(',', $msgIds);
            return ['msg_ids' => $msgIdsStr];
        } elseif (is_string($msgIds)) {
            return ['msg_ids' => $msgIds];
        } else {
            throw new InvalidArgumentException("Invalid msg_ids");
        }
    }
}
