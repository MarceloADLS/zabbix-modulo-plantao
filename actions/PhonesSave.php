<?php declare(strict_types = 1);

namespace Modules\Plantao\Actions;

use CController,
    CMessageHelper;

class PhonesSave extends CController {

    public function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        $fields = [
            'userid'   => 'required|db users.userid',
            'usrgrpid' => 'required|db usrgrp.usrgrpid',
            'phone'    => 'string'
        ];
        return $this->validateInput($fields);
    }

    public function checkPermissions(): bool {
        return ($this->getUserType() >= USER_TYPE_ZABBIX_ADMIN);
    }

    protected function doAction(): void {
        $userid = $this->getInput('userid');
        $usrgrpid = $this->getInput('usrgrpid');
        $phone = $this->getInput('phone', '');

        DBexecute(
            'INSERT INTO module_plantao_phones (userid, phone) VALUES (' . $userid . ', ' . zbx_dbstr($phone) . ') ' .
            'ON CONFLICT (userid) DO UPDATE SET phone = EXCLUDED.phone'
        );

        // Adiciona a mensagem de sucesso na sessão nativa do Zabbix
        CMessageHelper::setSuccessTitle('Telefone atualizado com sucesso.');

        // Força o redirecionamento imediato via HTTP
        header('Location: zabbix.php?action=phones.list&usrgrpid=' . $usrgrpid);
        exit;
    }
}
