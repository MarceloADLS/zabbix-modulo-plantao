<?php declare(strict_types = 1);

namespace Modules\Plantao\Actions;

use CController,
    CControllerResponseData;

class PlantaoList extends CController {

    public function init(): void {
        $this->disableCsrfValidation();
    }

    protected function checkInput(): bool {
        // Double check: Liberando os campos month e year na segurança do Zabbix
        $fields = [
            'usrgrpid' => 'db usrgrp.usrgrpid',
            'month'    => 'string',
            'year'     => 'string'
        ];
        return $this->validateInput($fields);
    }

    public function checkPermissions(): bool {
        return ($this->getUserType() >= USER_TYPE_ZABBIX_ADMIN);
    }

    protected function doAction(): void {
        // 1. Busca todos os grupos de usuários para o filtro do calendário
        $groups = [];
        $db_groups = DBselect('SELECT usrgrpid, name FROM usrgrp ORDER BY name');
        while ($group = DBfetch($db_groups)) {
            $groups[] = $group;
        }

        $selected_group = $this->getInput('usrgrpid', $groups[0]['usrgrpid'] ?? '0');

        // Captura dinamicamente o mês e ano validados da URL
        $month = (int)$this->getInput('month', date('n'));
        $year  = (int)$this->getInput('year', date('Y'));

        // Define a janela de busca exata do mês selecionado
        $first_day = sprintf('%04d-%02d-01', $year, $month);
        $last_day  = date('Y-m-t', strtotime($first_day));

        $sched_data = [];
        $team_users = [];

        if ($selected_group !== '0') {
            // 2. Busca os plantonistas agendados para o time selecionado no período correto
            $result = DBselect(
                'SELECT s.schedule_date, s.role_type, u.userid, u.name, u.surname, u.username, COALESCE(p.phone, \'\') AS phone ' .
                ' FROM module_plantao_schedule s ' .
                ' JOIN users u ON u.userid = s.userid ' .
                ' LEFT JOIN module_plantao_phones p ON p.userid = u.userid ' .
                ' WHERE s.usrgrpid = ' . $selected_group .
                '   AND s.schedule_date >= ' . zbx_dbstr($first_day) .
                '   AND s.schedule_date <= ' . zbx_dbstr($last_day)
            );

            while ($row = DBfetch($result)) {
                $sched_data[$row['schedule_date']][$row['role_type']] = $row;
            }

            // 3. Busca os usuários que pertencem a este grupo para popular os modais e autocompletes
            $users_res = DBselect(
                'SELECT u.userid, u.name, u.surname, u.username, COALESCE(p.phone, \'\') AS phone ' .
                ' FROM users u ' .
                ' JOIN users_groups ug ON ug.userid = u.userid ' .
                ' LEFT JOIN module_plantao_phones p ON p.userid = u.userid ' .
                ' WHERE ug.usrgrpid = ' . $selected_group .
                ' ORDER BY u.name, u.surname'
            );
            while ($user = DBfetch($users_res)) {
                $team_users[] = $user;
            }
        }

        $response = new CControllerResponseData([
            'groups'         => $groups,
            'selected_group' => $selected_group,
            'sched_data'     => $sched_data,
            'team_users'     => $team_users,
            'month'          => $month,
            'year'           => $year
        ]);

        $response->setTitle('Escala de Plantão por Equipe');
        $this->setResponse($response);
    }
}
