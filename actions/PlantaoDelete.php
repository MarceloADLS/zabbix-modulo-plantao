<?php declare(strict_types = 1);

namespace Modules\Plantao\Actions;

use CController,
    CMessageHelper;

class PlantaoDelete extends CController {

    public function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        $fields = [
            'date'     => 'required|string',
            'usrgrpid' => 'required|db usrgrp.usrgrpid',
            'month'    => 'string',
            'year'     => 'string'
        ];
        return $this->validateInput($fields);
    }

    public function checkPermissions(): bool {
        return ($this->getUserType() >= USER_TYPE_ZABBIX_ADMIN);
    }

    protected function doAction(): void {
        $date = $this->getInput('date');
        $usrgrpid = $this->getInput('usrgrpid');

        // Remove apenas o dia específico para a equipe ativa
        DBexecute('DELETE FROM module_plantao_schedule WHERE usrgrpid = ' . $usrgrpid . ' AND schedule_date = ' . zbx_dbstr($date));

        CMessageHelper::setSuccessTitle('Plantão do dia removido com sucesso.');

        $month = $this->getInput('month', date('n'));
        $year = $this->getInput('year', date('Y'));
        header('Location: zabbix.php?action=plantao.list&usrgrpid=' . $usrgrpid . '&month=' . $month . '&year=' . $year);
        exit;
    }
}
