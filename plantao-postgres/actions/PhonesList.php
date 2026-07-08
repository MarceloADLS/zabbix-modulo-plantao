<?php declare(strict_types = 1);

namespace Modules\Plantao\Actions;

use CController,
    CControllerResponseData;

class PhonesList extends CController {

    public function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        $fields = [
            'usrgrpid' => 'db usrgrp.usrgrpid',
            'success'  => 'string',
            'error'    => 'string'
        ];
        return $this->validateInput($fields);
    }

    public function checkPermissions(): bool {
        return ($this->getUserType() >= USER_TYPE_ZABBIX_ADMIN);
    }

    protected function doAction(): void {
        // 1. Puxa todos os grupos de usuários disponíveis no Zabbix para criar o filtro de times
        $groups = [];
        $db_groups = DBselect('SELECT usrgrpid, name FROM usrgrp ORDER BY name');
        while ($group = DBfetch($db_groups)) {
            $groups[] = $group;
        }

        // Pega o grupo selecionado no filtro (se não escolheu nenhum, pega o primeiro da lista)
        $selected_group = $this->getInput('usrgrpid', $groups[0]['usrgrpid'] ?? '0');

        // 2. Busca apenas os usuários pertencentes ao grupo/time selecionado
        $users = [];
        if ($selected_group !== '0') {
            $result = DBselect(
                'SELECT DISTINCT u.userid, u.name, u.surname, u.username, ' .
                ' COALESCE(p.phone, \'\') AS phone ' .
                ' FROM users u ' .
                ' JOIN users_groups ug ON ug.userid = u.userid ' .
                ' LEFT JOIN module_plantao_phones p ON p.userid = u.userid ' .
                ' WHERE ug.usrgrpid = ' . $selected_group .
                ' ORDER BY u.name, u.surname'
            );

            while ($row = DBfetch($result)) {
                $users[] = $row;
            }
        }

        $response = new CControllerResponseData([
            'groups'         => $groups,
            'selected_group' => $selected_group,
            'users'          => $users,
            'success'        => $this->getInput('success', ''),
            'error'          => $this->getInput('error', ''),
        ]);

        $response->setTitle('Telefones de Plantão por Time');
        $this->setResponse($response);
    }
}
