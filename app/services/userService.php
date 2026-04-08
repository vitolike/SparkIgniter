<?php
date_default_timezone_set('America/Sao_Paulo');

class userService extends Service {
    public function agent($id): ?array {
        return $this->qb->from('agents')->where(['agentCode' => $id, 'status' => 1])->fetchOne();
    }

    public function user($userCode, $agentCode): ?array {
        return $this->qb->from('users')->where(['userCode' => $userCode, 'agentCode' => $agentCode, 'status' => 1])->fetchOne();
    }

    public function agent_withdraw($agentCode, $amount): bool {
        return $this->qb->setRaw('balance', 'balance - ' . (float)$amount)->update('agents', [], ['agentCode' => $agentCode]);
    }

    public function agent_add($agentCode, $amount): bool {
        return $this->qb->setRaw('balance', 'balance + ' . (float)$amount)->update('agents', [], ['agentCode' => $agentCode]);
    }

    public function user_create($data) {
        $aCode = $data['agent_code'];
        $uCode = $data['user_code'];

        if ($this->user($uCode, $aCode)) {
            $this->response(['status' => 0, 'msg' => 'DUPLICATED_USER', 'timestamp' => date('Y-m-d H:i:s')], 400);
        }

        $agent = $this->agent($aCode);
        $insert = [
            'agentCode' => $aCode, 'userCode' => $uCode, 'aasUserCode' => $this->idGen->uuid(),
            'createdAt'=> date('Y-m-d H:i:s'), 'balance' => 0, 'status' => 1, 'agentType' => 1,
            'uuid' => $this->idGen->uuid(), 'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', 
            'currency' => $agent['currency'] ?? 'BRL'
        ];

        if ($this->qb->insert('users', $insert)) {
            $this->response(['status' => 1, 'msg' => 'SUCCESS', 'user_code' => $uCode, 'user_balance' => 0, 'uuid' => $insert['uuid'], 'currency' => $insert['currency'], 'timestamp' => date('Y-m-d H:i:s')], 200);
        }
        $this->response(['status' => 0, 'msg' => 'INTERNAL_ERROR'], 400);
    }

    public function user_all($data) {
        $users = $this->qb->from('users')->select('userCode user_code, status, totalDebit, totalCredit, balance, createdAt created_at')
                          ->where(['agentCode' => $data['agent_code'], 'status' => 1])->get();
        $this->response(['status' => 1, 'msg' => 'SUCCESS', 'user_list' => $users, 'timestamp' => date('Y-m-d H:i:s')], 200);
    }

    private function validate_amount($amount) {
        if (!isset($amount)) $this->response(['status' => 0, 'msg' => 'O campo "amount" é obrigatório', 'timestamp' => date('Y-m-d H:i:s')], 400);
        if ($amount <= 0) $this->response(['status' => 0, 'msg' => 'O campo "amount" precisa ter um valor valido', 'timestamp' => date('Y-m-d H:i:s')], 400);
    }

    public function user_deposit($data){
        $this->validate_amount($data['amount'] ?? null);
        $amount = (float)$data['amount'];
        $aCode = $data['agent_code'];
        $uCode = $data['user_code'];
        
        $agent = $this->agent($aCode);
        $user = $this->user($uCode, $aCode);

        if (!$user) $this->response(['status' => 0, 'msg' => 'USER_NOT_FOUND', 'timestamp' => date('Y-m-d H:i:s')], 400);
        if ($agent['balance'] < $amount) $this->response(['status' => 0, 'msg' => 'SALDO_INSUFICIENTE', 'timestamp' => date('Y-m-d H:i:s')], 400);

        if ($this->agent_withdraw($aCode, $amount) && $this->qb->setRaw('balance', 'balance + ' . $amount)->update('users', [], ['userCode' => $uCode, 'agentCode' => $aCode])) {
            $this->response(['status' => 1, 'msg' => 'SUCCESS', 'user_code' => $uCode, 'user_balance' => (float)($user['balance'] + $amount), 'agent_balance' => (float)($agent['balance'] - $amount), 'timestamp' => date('Y-m-d H:i:s')], 200);
        }
        $this->response(['status' => 0, 'msg' => 'INTERNAL_ERROR', 'timestamp' => date('Y-m-d H:i:s')], 400);
    }

    public function user_withdraw($data){
        $this->validate_amount($data['amount'] ?? null);
        $amount = (float)$data['amount'];
        $aCode = $data['agent_code'];
        $uCode = $data['user_code'];
        
        $user = $this->user($uCode, $aCode);
        $agent = $this->agent($aCode);

        if (!$user) $this->response(['status' => 0, 'msg' => 'USER_NOT_FOUND', 'timestamp' => date('Y-m-d H:i:s')], 400);
        if ($user['balance'] < $amount) $this->response(['status' => 0, 'msg' => 'SALDO_INSUFICIENTE', 'timestamp' => date('Y-m-d H:i:s')], 400);

        if ($this->agent_add($aCode, $amount) && $this->qb->setRaw('balance', 'balance - ' . $amount)->update('users', [], ['userCode' => $uCode, 'agentCode' => $aCode])) {
            $this->response(['status' => 1, 'msg' => 'SUCCESS', 'user_code' => $uCode, 'user_balance' => (float)($user['balance'] - $amount), 'agent_balance' => (float)($agent['balance'] + $amount), 'timestamp' => date('Y-m-d H:i:s')], 200);
        }
        $this->response(['status' => 0, 'msg' => 'INTERNAL_ERROR', 'timestamp' => date('Y-m-d H:i:s')], 400);
    }

    public function user_withdraw_reset($data){
        $aCode = $data['agent_code'];
        $uCode = $data['user_code'];
        $user = $this->user($uCode, $aCode);

        if (!$user) $this->response(['status' => 0, 'msg' => 'USER_NOT_FOUND', 'timestamp' => date('Y-m-d H:i:s')], 400);
        if ($user['balance'] <= 0) $this->response(['status' => 0, 'msg' => 'SALDO_JA_ZERADO', 'timestamp' => date('Y-m-d H:i:s')], 400);

        $amount = $user['balance'];
        
        if ($this->agent_add($aCode, $amount) && $this->qb->update('users', ['balance' => 0], ['userCode' => $uCode, 'agentCode' => $aCode])) {
            $this->response(['status' => 1, 'msg' => 'SUCCESS', 'user_code' => $uCode, 'user_balance' => 0, 'agent_balance' => (float)($this->agent($aCode)['balance']), 'timestamp' => date('Y-m-d H:i:s')], 200);
        }
        $this->response(['status' => 0, 'msg' => 'INTERNAL_ERROR', 'timestamp' => date('Y-m-d H:i:s')], 400);
    }

    public function money_info($data) {
        $aCode = $data['agent_code'];
        $uCode = $data['user_code'] ?? null;
        $agent = $this->agent($aCode);

        if (empty($uCode)) {
            $this->response(['status' => 1, 'msg' => 'SUCCESS', 'agent' => ['agent_code' => $aCode, 'balance' => floatval($agent['balance']), 'currency' => $agent['currency']], 'timestamp' => date('Y-m-d H:i:s')], 200);
        }

        $user = $this->user($uCode, $aCode);
        if (!$user) $this->response(['status' => 0, 'msg' => 'USER_NOT_FOUND', 'timestamp' => date('Y-m-d H:i:s')], 400);

        $this->response(['status' => 1, 'msg' => 'SUCCESS', 'user' => ['user_code' => $uCode, 'balance' => floatval($user['balance']), 'currency' => $user['currency']], 'agent' => ['agent_code' => $aCode, 'balance' => floatval($agent['balance']), 'currency' => $agent['currency']], 'timestamp' => date('Y-m-d H:i:s')], 200);
    }
}
