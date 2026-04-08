<?php
class agentService extends Service{
    public function agents($id): array {
        return $this->qb->get_where('agents', ['agentCode' => $id, 'status' => 1]);
    }
     
}