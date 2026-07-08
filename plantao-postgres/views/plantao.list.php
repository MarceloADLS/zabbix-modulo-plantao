<?php declare(strict_types = 1);

/**
 * @var CView $this
 * @var array $data
 */

$month             = (int)($data['month'] ?? date('n'));
$year              = (int)($data['year'] ?? date('Y'));
$selected_group    = $data['selected_group'] ?? '0';
$groups            = $data['groups'] ?? [];
$team_users        = $data['team_users'] ?? [];
$sched_data        = $data['sched_data'] ?? [];
$today_str         = date('Y-m-d');

$month_names = [
    1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril',
    5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto',
    9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'
];

$prev_month = $month - 1; $prev_year = $year;
if ($prev_month < 1) { $prev_month = 12; $prev_year--; }
$next_month = $month + 1; $next_year = $year;
if ($next_month > 12) { $next_month = 1; $next_year++; }

// Double check aqui: apontando estritamente para o dia 01 do mês corrente
$first_day_dt  = new DateTime(sprintf('%04d-%02d-01', $year, $month));
$first_weekday = (int)$first_day_dt->format('N') - 1;
$days_in_month = (int)$first_day_dt->format('t');

function getWeekMonday(int $y, int $m, int $d): string {
    $dt = new DateTime(sprintf('%04d-%02d-%02d', $y, $m, $d));
    $dow = (int)$dt->format('N');
    if ($dow > 1) $dt->modify('-' . ($dow - 1) . ' days');
    return $dt->format('Y-m-d');
}

ob_start();
?>
<style>
.plt-wrap { padding: 0 0 20px; color: #f2f2f2; }
.plt-nav { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; flex-wrap: wrap; background: #2b2b2b; padding: 10px 15px; border-radius: 4px; border: 1px solid #4f4f4f; }
.plt-nav .plt-month-label { margin: 0; font-size: 15px; font-weight: 600; text-align: center; color: #c8d8e4; min-width: 120px; }
.plt-cal { width: 100%; border-collapse: collapse; table-layout: fixed; height: calc(100vh - 340px); min-height: 480px; }
.plt-cal th { background: #303030; color: #b0c4d4; text-align: center; padding: 8px 4px; font-size: 12px; font-weight: 600; border: 1px solid #4f4f4f; }
.plt-cal td { border: 1px solid #4f4f4f; vertical-align: top; padding: 0; cursor: pointer; transition: background 0.1s; height: 1%; background: #2b2b2b; position: relative; }
.plt-cell-inner { position: absolute; top: 0; left: 0; right: 0; bottom: 0; overflow: hidden; padding: 7px 8px; }
.plt-cal td:hover { background: #383838; }
.plt-cal td.empty { background: #222; cursor: default; }
.plt-cal td.today { background: #2c2211; border-color: #e99003; }
.plt-cal td.today:hover { background: #3a2b10; }
.plt-cal td.has-tech { background: #162616; }
.plt-cal td.has-tech:hover { background: #1c3a1c; }
.plt-cal td.past { opacity: 0.5; }
.plt-day-num { font-size: 13px; font-weight: 700; color: #c8d8e4; display: flex; justify-content: space-between; align-items: center; }
.plt-today-badge { background: #e99003; color: #1a1000; border-radius: 3px; padding: 0 5px; font-size: 10px; font-weight: 700; }
.plt-tech { font-size: 11px; color: #59dbff; font-weight: 600; margin-top: 4px; }
.plt-phone { font-size: 10px; color: #0faabc; margin-top: 2px; }
.plt-no-phone { font-size: 10px; color: #555; margin-top: 2px; font-style: italic; }
.plt-reserva-label { font-size: 9px; color: #666; margin-top: 5px; text-transform: uppercase; }
.plt-tech-reserva { font-size: 11px; color: #59dbff; font-weight: 600; margin-top: 1px; }
.plt-phone-reserva { font-size: 10px; color: #0faabc; margin-top: 1px; }
.plt-del { font-size: 10px; color: #e45959; text-decoration: none; font-weight: normal; }
.plt-del:hover { text-decoration: underline; color: #ff7070; }
.plt-form-panel { margin-top: 24px; background: #303030; border: 1px solid #4f4f4f; border-radius: 4px; padding: 20px 24px; }
.plt-form-panel h3 { margin: 0 0 18px; font-size: 15px; font-weight: 600; color: #f2f2f2; }
.plt-form-row { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 10px; }
.plt-form-row label { font-weight: 600; font-size: 13px; color: #9ab; }
.plt-multiselect-wrap { flex: 1; min-width: 0; position: relative; }
.plt-select-row { display: flex; align-items: stretch; flex: 0 1 300px; min-width: 220px; }
.plt-select-row .multiselect { flex: 1; min-width: 0; margin-right: 0 !important; border-radius: 4px 0 0 4px; border-right: 0 !important; }
.plt-select-row .btn { border-radius: 0 4px 4px 0; white-space: nowrap; flex-shrink: 0; align-self: stretch; }
.plt-ac-dropdown { display: none; position: absolute; z-index: 1100; top: 100%; left: 0; right: 0; background: #2b2b2b; border: 1px solid #4f4f4f; max-height: 220px; overflow-y: auto; }
.plt-ac-dropdown.open { display: block; }
.plt-ac-item { padding: 7px 10px; font-size: 13px; color: #f2f2f2; cursor: pointer; border-bottom: 1px solid #383838; }
.plt-ac-item:last-child { border-bottom: none; }
.plt-ac-item:hover { background: #505050; }
.plt-ac-item mark { background: none; color: #e99003; font-weight: 700; }
.plt-week-info { font-size: 12px; color: #0faabc; font-style: italic; margin-top: 5px; }
</style>

<div id="plt-modal-bg" class="overlay-bg" style="display:none;" onclick="pltCloseModal()"></div>

<div id="plt-modal" class="overlay-dialogue modal-popup modal-popup-medium" style="display:none; top:60px; left:50%; transform:translateX(-50%);" role="dialog" aria-modal="true">
    <div class="overlay-dialogue-header">
        <h4 id="plt-modal-title">Selecionar Técnico de Plantão</h4>
        <button class="btn-overlay-close" title="Close" onclick="pltCloseModal()"></button>
    </div>
    <div class="overlay-dialogue-body">
        <table class="list-table" id="plt-modal-table">
            <thead><tr><th>Nome</th><th>Telefone</th></tr></thead>
            <tbody>
                <?php foreach ($team_users as $tu): 
                    $fullname = htmlspecialchars($tu['name'] . ' ' . $tu['surname']);
                    $display_val = $tu['name'] . ' ' . $tu['surname'] . ($tu['phone'] ? ' (' . $tu['phone'] . ')' : '');
                ?>
                <tr>
                    <td><a href="javascript:void(0)" onclick="pltPickUser(<?php echo (int)$tu['userid']; ?>, <?php echo htmlspecialchars(json_encode($display_val)); ?>)"><?php echo $fullname; ?></a></td>
                    <td><?php echo $tu['phone'] ? htmlspecialchars($tu['phone']) : '<span style="color:#666;font-style:italic;">sem telefone</span>'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="overlay-dialogue-footer"><button type="button" class="btn-alt" onclick="pltCloseModal()">Cancelar</button></div>
</div>

<div class="plt-wrap">
    <div class="plt-nav">
        <button type="button" class="btn-icon zi-chevron-left" title="Mês anterior" onclick="pltNavigate(<?php echo $prev_month; ?>, <?php echo $prev_year; ?>)"></button>
        <span class="plt-month-label"><?php echo $month_names[$month] . ' ' . $year; ?></span>
        <button type="button" class="btn-icon zi-chevron-right" title="Próximo mês" onclick="pltNavigate(<?php echo $next_month; ?>, <?php echo $next_year; ?>)"></button>
        
        <label style="color: #9ab; font-weight: 600; margin-left: auto;">Time:</label>
        <select id="plt-team-select" onchange="pltNavigate(<?php echo $month; ?>, <?php echo $year; ?>)" style="background:#383838; color:#fff; border:1px solid #4f4f4f; padding:4px 8px; border-radius:3px;">
            <?php foreach ($groups as $g): ?>
                <option value="<?php echo $g['usrgrpid']; ?>" <?php echo $selected_group == $g['usrgrpid'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($g['name']); ?></option>
                <?php endforeach; ?>
        </select>

        <button type="button" class="btn btn-alt" onclick="location.href='zabbix.php?action=phones.list&usrgrpid=' + document.getElementById('plt-team-select').value;">Gerenciar Telefones</button>
    </div>

    <table class="plt-cal">
        <thead>
            <tr><th>Segunda</th><th>Terça</th><th>Quarta</th><th>Quinta</th><th>Sexta</th><th>Sábado</th><th>Domingo</th></tr>
        </thead>
        <tbody>
        <?php
        $day = 1; $cell = 0;
        echo '<tr>';
        for ($i = 0; $i < $first_weekday; $i++) {
            echo '<td class="empty"></td>'; $cell++;
        }
        while ($day <= $days_in_month) {
            $date_str = sprintf('%04d-%02d-%02d', $year, $month, $day);
            $is_today = ($date_str === $today_str);
            $is_past  = ($date_str < $today_str);
            
            $entry_principal = $sched_data[$date_str]['principal'] ?? null;
            $entry_reserva   = $sched_data[$date_str]['reserva'] ?? null;

            $cls = [];
            if ($is_today) $cls[] = 'today';
            if ($is_past)  $cls[] = 'past';
            if ($entry_principal) $cls[] = 'has-tech';
            $cls_str = $cls ? ' class="' . implode(' ', $cls) . '"' : '';

            $uid_js   = $entry_principal ? (int)$entry_principal['userid'] : 'null';
            $sname_js = $entry_principal ? json_encode($entry_principal['name'] . ' ' . $entry_principal['surname']) : 'null';
            $uid_res_js   = $entry_reserva ? (int)$entry_reserva['userid'] : 'null';
            $sname_res_js = $entry_reserva ? json_encode($entry_reserva['name'] . ' ' . $entry_reserva['surname']) : 'null';

            echo '<td' . $cls_str . ' onclick="pltSelectDay(\'' . $date_str . '\', ' . $uid_js . ', ' . $sname_js . ', ' . $uid_res_js . ', ' . $sname_res_js . ')">';
            echo '<div class="plt-cell-inner">';
            echo '<div class="plt-day-num"><span>' . $day . '</span>';
            
            if ($is_today) {
                echo '<span class="plt-today-badge">Hoje</span>';
            }

            if ($entry_principal) {
                $del_url = 'zabbix.php?action=plantao.delete&date=' . $date_str . '&usrgrpid=' . $selected_group . '&month=' . $month . '&year=' . $year;
                echo '<a class="plt-del" href="' . $del_url . '" tabindex="-1" onclick="event.stopPropagation(); return confirm(\'Remover o plantão deste dia?\')">[x]</a>';
            }
            echo '</div>';

            if ($entry_principal) {
                echo '<div class="plt-tech">' . htmlspecialchars($entry_principal['name'] . ' ' . $entry_principal['surname']) . '</div>';
                echo $entry_principal['phone'] ? '<div class="plt-phone">' . htmlspecialchars($entry_principal['phone']) . '</div>' : '<div class="plt-no-phone">sem telefone</div>';
                
                if ($entry_reserva) {
                    echo '<div class="plt-reserva-label">Reserva:</div>';
                    echo '<div class="plt-tech-reserva">' . htmlspecialchars($entry_reserva['name'] . ' ' . $entry_reserva['surname']) . '</div>';
                    echo $entry_reserva['phone'] ? '<div class="plt-phone-reserva">' . htmlspecialchars($entry_reserva['phone']) . '</div>' : '';
                }
            }

            echo '</div></td>';
            $day++; $cell++;
            if ($cell % 7 === 0 && $day <= $days_in_month) echo '</tr><tr>';
        }
        $remaining = $cell % 7;
        if ($remaining > 0) {
            for ($i = 0; $i < (7 - $remaining); $i++) echo '<td class="empty"></td>';
        }
        echo '</tr>';
        ?>
        </tbody>
    </table>

    <div class="plt-form-panel">
        <h3 id="plt-form-title">Escalar Técnico (Dia Selecionado: <?php echo date('d/m/Y'); ?>)</h3>
        <form method="post" action="zabbix.php?action=plantao.save" id="plt-form">
            <input type="hidden" name="sid" value="<?php echo CSessionHelper::get('sid'); ?>">
            <input type="hidden" name="month" value="<?php echo $month; ?>">
            <input type="hidden" name="year" value="<?php echo $year; ?>">
            <input type="hidden" name="usrgrpid" value="<?php echo $selected_group; ?>">
            <input type="hidden" id="userid" name="userid" value="">
            <input type="hidden" id="userid_reserva" name="userid_reserva" value="">
            <input type="hidden" id="schedule_date" name="schedule_date" value="<?php echo $today_str; ?>">
            <input type="hidden" id="save_mode" name="save_mode" value="day">

            <div class="plt-form-row">
                <label>Técnico:</label>
                <div class="plt-select-row">
                    <div class="multiselect plt-multiselect-wrap">
                        <input type="text" id="plt-selected-name" placeholder="Nenhum técnico selecionado" autocomplete="off" oninput="pltAcFilter(this.value)">
                        <div class="plt-ac-dropdown" id="plt-ac-dropdown"></div>
                    </div>
                    <button type="button" class="btn" onclick="pltOpenModal('main')">Selecionar</button>
                </div>

                <label style="margin-left:16px;">Técnico Reserva:</label>
                <div class="plt-select-row">
                    <div class="multiselect plt-multiselect-wrap">
                        <input type="text" id="plt-selected-reserva" placeholder="Nenhum técnico selecionado" autocomplete="off" oninput="pltAcFilterReserva(this.value)">
                        <div class="plt-ac-dropdown" id="plt-ac-dropdown-reserva"></div>
                    </div>
                    <button type="button" class="btn" onclick="pltOpenModal('reserva')">Selecionar</button>
                </div>
            </div>

            <div class="plt-form-row" style="margin-top: 15px; justify-content: flex-start; gap: 12px;">
                <button type="submit" class="btn" onclick="document.getElementById('save_mode').value='day';">Salvar Apenas Este Dia</button>
                <button type="submit" class="btn btn-alt" onclick="document.getElementById('save_mode').value='week';">Salvar Semana Inteira</button>
                <div class="plt-week-info" id="plt-week-info"></div>
            </div>
        </form>
    </div>
</div>

<script>
function pltNavigate(m, y) {
    var group = document.getElementById('plt-team-select').value;
    window.location.href = 'zabbix.php?action=plantao.list&month=' + m + '&year=' + y + '&usrgrpid=' + group;
}

function pltFormatDateStr(dateStr) {
    var p = dateStr.split('-');
    return p[2] + '/' + p[1] + '/' + p[0];
}

function pltWeekRange(dateStr) {
    var d = new Date(dateStr + 'T00:00:00');
    var dow = d.getDay();
    var diff = (dow === 0) ? 6 : dow - 1;
    var mon = new Date(d); mon.setDate(d.getDate() - diff);
    var sun = new Date(mon); sun.setDate(mon.getDate() + 6);
    function fmt(dt) { return ('0'+dt.getDate()).slice(-2)+'/'+('0'+(dt.getMonth()+1)).slice(-2)+'/'+dt.getFullYear(); }
    return 'Gera Escala: ' + fmt(mon) + ' a ' + fmt(sun);
}

function pltUpdateWeekInfo() {
    var val = document.getElementById('schedule_date').value;
    if (val) {
        document.getElementById('plt-week-info').textContent = '| ' + pltWeekRange(val);
        document.getElementById('plt-form-title').textContent = 'Escalar Técnico (Dia Selecionado: ' + pltFormatDateStr(val) + ')';
    }
}

var pltModalCtx = 'main';

function pltSelectDay(date, userid, displayName, useridReserva, displayReserva) {
    document.getElementById('schedule_date').value = date;
    pltUpdateWeekInfo();
    
    document.getElementById('userid').value = userid || '';
    document.getElementById('plt-selected-name').value = displayName || '';
    
    document.getElementById('userid_reserva').value = useridReserva || '';
    document.getElementById('plt-selected-reserva').value = displayReserva || '';

    document.getElementById('plt-form').scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function pltOpenModal(ctx) {
    pltModalCtx = ctx || 'main';
    document.getElementById('plt-modal-title').textContent = (pltModalCtx === 'reserva') ? 'Selecionar Técnico Reserva' : 'Selecionar Técnico de Plantão';
    document.getElementById('plt-modal').style.display = 'block';
    document.getElementById('plt-modal-bg').style.display = 'block';
}

function pltCloseModal() {
    document.getElementById('plt-modal').style.display = 'none';
    document.getElementById('plt-modal-bg').style.display = 'none';
}

function pltPickUser(userid, displayName) {
    if (pltModalCtx === 'reserva') {
        document.getElementById('userid_reserva').value = userid;
        document.getElementById('plt-selected-reserva').value = displayName;
    } else {
        document.getElementById('userid').value = userid;
        document.getElementById('plt-selected-name').value = displayName;
    }
    pltCloseModal();
}

var PLT_USERS = <?php echo json_encode(array_values(array_map(fn($u) => [
    'userid'  => (int)$u['userid'],
    'name'    => $u['name'] . ' ' . $u['surname'],
    'display' => $u['name'] . ' ' . $u['surname'] . ($u['phone'] ? ' (' . $u['phone'] . ')' : '')
], $team_users))); ?>;

function pltAcHighlight(text, query) {
    if (!query) return text;
    var re = new RegExp('(' + query.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&') + ')', 'gi');
    return text.replace(re, '<mark>$1</mark>');
}

function pltAcBuild(ddid, hiddenid, inputid, q) {
    var dd = document.getElementById(ddid);
    if (!q) { dd.innerHTML = ''; dd.classList.remove('open'); return; }
    var qL = q.toLowerCase();
    var matches = PLT_USERS.filter(function(u) { return u.name.toLowerCase().indexOf(qL) !== -1; });
    if (!matches.length) { dd.innerHTML = ''; dd.classList.remove('open'); return; }
    
    dd.innerHTML = matches.map(function(u) {
        return '<div class="plt-ac-item" data-userid="' + u.userid + '" data-display="' + u.display.replace(/"/g, '&quot;') + '">' + pltAcHighlight(u.name, q) + '</div>';
    }).join('');
    dd.classList.add('open');

    dd.querySelectorAll('.plt-ac-item').forEach(function(el) {
        el.addEventListener('mousedown', function(e) {
            e.preventDefault();
            document.getElementById(hiddenid).value = this.dataset.userid;
            document.getElementById(inputid).value = this.dataset.display;
            dd.innerHTML = ''; dd.classList.remove('open');
        });
    });
}

function pltAcFilter(q) { pltAcBuild('plt-ac-dropdown', 'userid', 'plt-selected-name', q); }
function pltAcFilterReserva(q) { pltAcBuild('plt-ac-dropdown-reserva', 'userid_reserva', 'plt-selected-reserva', q); }

document.getElementById('plt-selected-name').addEventListener('blur', function() {
    setTimeout(function() { var dd = document.getElementById('plt-ac-dropdown'); dd.innerHTML = ''; dd.classList.remove('open'); }, 150);
});
document.getElementById('plt-selected-reserva').addEventListener('blur', function() {
    setTimeout(function() { var dd = document.getElementById('plt-ac-dropdown-reserva'); dd.innerHTML = ''; dd.classList.remove('open'); }, 150);
});

document.getElementById('plt-modal-bg').addEventListener('click', pltCloseModal);
document.addEventListener('keydown', function(e) { if (e.key === 'Escape') pltCloseModal(); });
pltUpdateWeekInfo();
</script>

<?php
$page_content = ob_get_clean();
$raw = new class($page_content) {
    public function __construct(private string $html) {}
    public function toString(bool $destroy = true): string { return $this->html; }
};

(new CHtmlPage())
    ->setTitle('Escala de Plantão por Equipe')
    ->addItem($raw)
    ->show();
