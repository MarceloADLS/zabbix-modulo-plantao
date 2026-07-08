<?php declare(strict_types = 1);

namespace Modules\Plantao;

use Zabbix\Core\CModule,
    APP,
    CMenuItem,
    CMenu,
    CWebUser;

class Module extends CModule {

    public function init(): void {
        // Apenas admins e super admins veem o menu
        if (CWebUser::$data['type'] < USER_TYPE_ZABBIX_ADMIN) {
            return;
        }

        $menu = APP::Component()->get('menu.main');

        $menu->insertAfter(_('Reports'),
            (new CMenuItem(_('Plantão')))->setIcon('zi-calendar-check')
                ->setSubMenu((new CMenu())
                    ->add((new CMenuItem(_('Escala')))->setAction('plantao.list'))
                    ->add((new CMenuItem(_('Telefones')))->setAction('phones.list'))
                )
        );
    }

    public function onInstall(): void {
        // Telefone de contato por técnico (userid é a chave única).
        DBexecute(
            'CREATE TABLE IF NOT EXISTS module_plantao_phones (' .
            'userid BIGINT NOT NULL,' .
            'phone VARCHAR(100) NOT NULL DEFAULT \'\',' .
            'PRIMARY KEY (userid)' .
            ')'
        );

        // Escala de plantão por equipe/dia/papel (principal ou reserva).
        DBexecute(
            'CREATE TABLE IF NOT EXISTS module_plantao_schedule (' .
            'scheduleid SERIAL NOT NULL,' .
            'usrgrpid BIGINT NOT NULL,' .
            'userid BIGINT NOT NULL,' .
            'role_type VARCHAR(20) NOT NULL,' .
            'schedule_date DATE NOT NULL,' .
            'created_by BIGINT NOT NULL DEFAULT 0,' .
            'created_at INTEGER NOT NULL DEFAULT 0,' .
            'PRIMARY KEY (scheduleid),' .
            'CONSTRAINT module_plantao_schedule_uniq UNIQUE (usrgrpid, schedule_date, role_type)' .
            ')'
        );
    }

    public function onUninstall(): void {
        DBexecute('DROP TABLE IF EXISTS module_plantao_schedule');
        DBexecute('DROP TABLE IF EXISTS module_plantao_phones');
    }
}
