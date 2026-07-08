<?php declare(strict_types = 1);

/**
 * @var CView $this
 * @var array $data
 */

$html_output = (new CHtmlPage())
    ->setTitle('Telefones de Plantão por Time');

// Formulário do Filtro no Topo
$filter_form = (new CForm('get'))
    ->setAttribute('name', 'filter_form')
    ->addVar('action', 'phones.list');

$select_group = (new CSelect('usrgrpid'))
    ->setValue($data['selected_group'])
    ->setAttribute('onchange', 'document.filter_form.submit();');

foreach ($data['groups'] as $group) {
    $select_group->addOption(new CSelectOption($group['usrgrpid'], $group['name']));
}

$filter_form->addItem((new CList())
    ->addItem(['Selecionar Time (Grupo Zabbix): ', $select_group])
);

$html_output->addItem($filter_form);

// Tabela de Usuários com coluna de ações
$table = (new CTableInfo())
    ->setHeader([
        'Usuário',
        'Nome',
        'Telefone Atual',
        'Ações'
    ]);

foreach ($data['users'] as $user) {
    $username = $user['username'];
    $fullname = trim($user['name'] . ' ' . $user['surname']);
    if ($fullname === '') {
        $fullname = '-';
    }

    // Campo de texto nativo para o telefone
    $phone_input = (new CTextBox('phone', $user['phone']))
        ->setWidth(ZBX_TEXTAREA_MEDIUM_WIDTH);

// Formulário individual de envio para a Action de salvamento
    $row_form = (new CForm('post'))
        ->addVar('action', 'phones.save')
        ->addVar('sid', CSessionHelper::get('sid')) // Injeta o token de sessão do Zabbix 7.4
        ->addVar('usrgrpid', $data['selected_group'])
        ->addVar('userid', $user['userid'])
        ->addItem($phone_input)
        ->addItem((new CSubmit('submit', 'Salvar'))->addClass(ZBX_STYLE_BTN_LINK));

    $table->addRow([
        $username,
        $fullname,
        $user['phone'] !== '' ? $user['phone'] : (new CSpan('Sem telefone'))->addClass(ZBX_STYLE_RED),
        $row_form
    ]);
}

$html_output->addItem($table);
$html_output->show();
