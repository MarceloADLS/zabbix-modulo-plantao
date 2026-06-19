<?php declare(strict_types = 1);

namespace Modules\Plantao\Actions;

use CController,
    CMessageHelper,
    CWebUser;

class PlantaoSave extends CController {

    public function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        $fields = [
            'usrgrpid'      => 'required|db usrgrp.usrgrpid',
            'userid'        => 'db users.userid',
            'userid_reserva' => 'db users.userid',
            'schedule_date' => 'required|string',
            'save_mode'     => 'string',
            'month'         => 'string',
            'year'          => 'string'
        ];
        return $this->validateInput($fields);
    }

    public function checkPermissions(): bool {
        return ($this->getUserType() >= USER_TYPE_ZABBIX_ADMIN);
    }

    protected function doAction(): void {
        $usrgrpid = $this->getInput('usrgrpid');
        $base_date = $this->getInput('schedule_date');
        $uid_main = $this->getInput('userid', '');
        $uid_res  = $this->getInput('userid_reserva', '');
        $mode     = $this->getInput('save_mode', 'day');
        
        $current_user = CWebUser::$data['userid'] ?? '0';
        $now = time();

        // Monta a lista de datas que serão afetadas
        $target_dates = [];
        if ($mode === 'week') {
            // Pega a segunda-feira da semana selecionada
            $dt = new \DateTime($base_date);
            $dow = (int)$dt->format('N');
            if ($dow > 1) {
                $dt->modify('-' . ($dow - 1) . ' days');
            }
            // Varre de segunda a domingo
            for ($i = 0; $i < 7; $i++) {
                $target_dates[] = $dt->format('Y-m-d');
                $dt->modify('+1 day');
            }
        } else {
            // Apenas o dia clicado
            $target_dates[] = $base_date;
        }

        // Executa a gravação para as datas selecionadas
        foreach ($target_dates as $date) {
            // Principal
            if ($uid_main !== '') {
                DBexecute('REPLACE INTO module_plantao_schedule (usrgrpid, userid, role_type, schedule_date, created_by, created_at) ' .
                    'VALUES (' . $usrgrpid . ', ' . $uid_main . ', \'principal\', ' . zbx_dbstr($date) . ', ' . $current_user . ', ' . $now . ')');
            } else {
                DBexecute('DELETE FROM module_plantao_schedule WHERE usrgrpid = ' . $usrgrpid . ' AND schedule_date = ' . zbx_dbstr($date) . ' AND role_type = \'principal\'');
            }

            // Reserva
            if ($uid_res !== '') {
                DBexecute('REPLACE INTO module_plantao_schedule (usrgrpid, userid, role_type, schedule_date, created_by, created_at) ' .
                    'VALUES (' . $usrgrpid . ', ' . $uid_res . ', \'reserva\', ' . zbx_dbstr($date) . ', ' . $current_user . ', ' . $now . ')');
            } else {
                DBexecute('DELETE FROM module_plantao_schedule WHERE usrgrpid = ' . $usrgrpid . ' AND schedule_date = ' . zbx_dbstr($date) . ' AND role_type = \'reserva\'');
            }
        }

        CMessageHelper::setSuccessTitle($mode === 'week' ? 'Escala da semana inteira gerada.' : 'Plantão diário atualizado.');

        $month = $this->getInput('month', date('n'));
        $year = $this->getInput('year', date('Y'));
        header('Location: zabbix.php?action=plantao.list&usrgrpid=' . $usrgrpid . '&month=' . $month . '&year=' . $year);
        exit;
    }
}
