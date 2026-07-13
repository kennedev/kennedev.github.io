<?php
require_once __DIR__ . '/lib/auth.php';
require_once __DIR__ . '/lib/layout.php';
require_once __DIR__ . '/lib/img.php';
require_once __DIR__ . '/../lib/publico.php'; // foto_url, caracteristicas_mapa, tipo_label, status_label
exigir_login();

$TIPOS = array('casa','apartamento','sobrado','terreno','comercial','sala','galpao','chacara');
$STATUSES = array('disponivel','reservado','vendido','alugado','inativo');
$INTS = array('dormitorios','suites','banheiros','vagas');
$DECS = array('preco','condominio','iptu','area_util','area_total','latitude','longitude');

/** Converte texto digitado (1.290.000,00 / 3200,50 / 180) em float ou null. */
function parse_num($s) {
    $s = preg_replace('/[^\d,\.]/', '', (string) $s);
    if ($s === '') return null;
    if (strpos($s, ',') !== false) {            // vírgula = decimal (BR)
        $s = str_replace('.', '', $s);
        $s = str_replace(',', '.', $s);
    } elseif (substr_count($s, '.') > 1) {       // vários pontos = milhar
        $s = str_replace('.', '', $s);
    }
    return is_numeric($s) ? (float) $s : null;
}

/** Float do banco -> string limpa para preencher o input. */
function num_str($v) {
    if ($v === null || $v === '') return '';
    return rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
}

function dir_fotos($id) { return __DIR__ . '/../uploads/imoveis/' . (int) $id . '/'; }

$id = (int) ($_GET['id'] ?? 0);
$erro = '';

// ── POST ──────────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $acao = $_POST['acao'] ?? '';
    $pid = (int) ($_POST['id'] ?? 0);

    if ($acao === 'capa' && $pid) {
        $fid = (int) ($_POST['foto_id'] ?? 0);
        db()->prepare('UPDATE imovel_fotos SET capa = 0 WHERE imovel_id = ?')->execute(array($pid));
        db()->prepare('UPDATE imovel_fotos SET capa = 1 WHERE id = ? AND imovel_id = ?')->execute(array($fid, $pid));
        flash_set('Foto de capa definida.');
        header('Location: imovel-editar.php?id=' . $pid); exit;

    } elseif ($acao === 'excluir_foto' && $pid) {
        $fid = (int) ($_POST['foto_id'] ?? 0);
        $st = db()->prepare('SELECT arquivo, capa FROM imovel_fotos WHERE id = ? AND imovel_id = ?');
        $st->execute(array($fid, $pid));
        if ($f = $st->fetch()) {
            db()->prepare('DELETE FROM imovel_fotos WHERE id = ?')->execute(array($fid));
            img_remover_arquivos(dir_fotos($pid), $f['arquivo']);
            if ($f['capa']) { // promove a próxima a capa
                db()->prepare('UPDATE imovel_fotos SET capa = 1 WHERE imovel_id = ? ORDER BY ordem ASC, id ASC LIMIT 1')->execute(array($pid));
            }
        }
        flash_set('Foto removida.');
        header('Location: imovel-editar.php?id=' . $pid); exit;

    } elseif ($acao === 'salvar_foto' && $pid) {
        $fid = (int) ($_POST['foto_id'] ?? 0);
        db()->prepare('UPDATE imovel_fotos SET legenda = ?, ordem = ? WHERE id = ? AND imovel_id = ?')
            ->execute(array(trim($_POST['legenda'] ?? ''), (int) ($_POST['ordem'] ?? 0), $fid, $pid));
        flash_set('Foto atualizada.');
        header('Location: imovel-editar.php?id=' . $pid); exit;

    } elseif ($acao === 'salvar') {
        $id = $pid;
        $dados = array(
            'referencia' => strtoupper(trim($_POST['referencia'] ?? '')),
            'titulo'     => trim($_POST['titulo'] ?? ''),
            'finalidade' => in_array($_POST['finalidade'] ?? '', array('venda','aluguel'), true) ? $_POST['finalidade'] : 'venda',
            'tipo'       => in_array($_POST['tipo'] ?? '', $TIPOS, true) ? $_POST['tipo'] : 'casa',
            'status'     => in_array($_POST['status'] ?? '', $STATUSES, true) ? $_POST['status'] : 'disponivel',
            'descricao'  => trim($_POST['descricao'] ?? ''),
            'cep'        => trim($_POST['cep'] ?? ''),
            'logradouro' => trim($_POST['logradouro'] ?? ''),
            'numero'     => trim($_POST['numero'] ?? ''),
            'bairro'     => trim($_POST['bairro'] ?? ''),
            'cidade'     => trim($_POST['cidade'] ?? ''),
            'uf'         => strtoupper(substr(trim($_POST['uf'] ?? ''), 0, 2)),
            'mostrar_endereco' => !empty($_POST['mostrar_endereco']) ? 1 : 0,
            'destaque'   => !empty($_POST['destaque']) ? 1 : 0,
            'caracteristicas' => json_encode(array_values(array_intersect(array_keys(caracteristicas_mapa()), (array) ($_POST['caracteristicas'] ?? array()))), JSON_UNESCAPED_UNICODE),
        );
        foreach ($INTS as $c) $dados[$c] = max(0, (int) ($_POST[$c] ?? 0));
        foreach ($DECS as $c) $dados[$c] = parse_num($_POST[$c] ?? '');

        if ($dados['titulo'] === '') {
            $erro = 'Informe o título do imóvel.';
        } elseif ($dados['referencia'] !== '') {
            $chk = db()->prepare('SELECT id FROM imoveis WHERE referencia = ? AND id <> ?');
            $chk->execute(array($dados['referencia'], $id));
            if ($chk->fetch()) $erro = 'Já existe um imóvel com a referência ' . $dados['referencia'] . '.';
        }

        if (!$erro) {
            $cols = array('referencia','titulo','finalidade','tipo','status','descricao','cep','logradouro','numero','bairro','cidade','uf','mostrar_endereco','destaque','caracteristicas');
            $cols = array_merge($cols, $INTS, $DECS);
            if ($id) {
                $set = implode(', ', array_map(function ($c) { return "$c = ?"; }, $cols));
                $vals = array_map(function ($c) use ($dados) { return $dados[$c]; }, $cols);
                $vals[] = $id;
                db()->prepare("UPDATE imoveis SET $set WHERE id = ?")->execute($vals);
            } else {
                $ph = implode(', ', array_fill(0, count($cols), '?'));
                $vals = array_map(function ($c) use ($dados) { return $dados[$c]; }, $cols);
                db()->prepare('INSERT INTO imoveis (' . implode(', ', $cols) . ") VALUES ($ph)")->execute($vals);
                $id = (int) db()->lastInsertId();
                if ($dados['referencia'] === '') { // gera referência automática
                    $ref = 'LI' . str_pad((string) $id, 3, '0', STR_PAD_LEFT);
                    db()->prepare('UPDATE imoveis SET referencia = ? WHERE id = ?')->execute(array($ref, $id));
                }
            }

            // Upload de novas fotos
            $msgFotos = '';
            if (!empty($_FILES['fotos']) && is_array($_FILES['fotos']['name'])) {
                $dir = dir_fotos($id);
                $jaTem = (int) db()->query('SELECT COUNT(*) FROM imovel_fotos WHERE imovel_id = ' . $id)->fetchColumn();
                $temCapa = (int) db()->query('SELECT COUNT(*) FROM imovel_fotos WHERE imovel_id = ' . $id . ' AND capa = 1')->fetchColumn();
                $maxOrdem = (int) db()->query('SELECT COALESCE(MAX(ordem), -1) FROM imovel_fotos WHERE imovel_id = ' . $id)->fetchColumn();
                $erros = array();
                $n = count($_FILES['fotos']['name']);
                for ($k = 0; $k < $n; $k++) {
                    if ($_FILES['fotos']['error'][$k] === UPLOAD_ERR_NO_FILE) continue;
                    if ($jaTem >= IMG_MAX_POR_IMOVEL) { $erros[] = 'Limite de ' . IMG_MAX_POR_IMOVEL . ' fotos atingido.'; break; }
                    $file = array(
                        'name' => $_FILES['fotos']['name'][$k], 'type' => $_FILES['fotos']['type'][$k],
                        'tmp_name' => $_FILES['fotos']['tmp_name'][$k], 'error' => $_FILES['fotos']['error'][$k],
                        'size' => $_FILES['fotos']['size'][$k],
                    );
                    try {
                        $arq = img_processar($file, $dir);
                        $maxOrdem++;
                        $capa = $temCapa === 0 ? 1 : 0; $temCapa = 1;
                        db()->prepare('INSERT INTO imovel_fotos (imovel_id, arquivo, ordem, capa) VALUES (?,?,?,?)')
                            ->execute(array($id, $arq, $maxOrdem, $capa));
                        $jaTem++;
                    } catch (Exception $ex) {
                        $erros[] = $ex->getMessage();
                    }
                }
                if ($erros) $msgFotos = ' Avisos: ' . implode(' ', $erros);
            }

            flash_set('Imóvel salvo.' . ($msgFotos ?? ''));
            header('Location: imovel-editar.php?id=' . $id); exit;
        }
        // com erro: cai para o render mantendo os valores postados
    }
}

// ── Carrega dados para o formulário ───────────────────────────────────────────
$im = array('id'=>0,'referencia'=>'','titulo'=>'','finalidade'=>'venda','tipo'=>'casa','status'=>'disponivel',
    'preco'=>'','condominio'=>'','iptu'=>'','descricao'=>'','dormitorios'=>0,'suites'=>0,'banheiros'=>0,'vagas'=>0,
    'area_util'=>'','area_total'=>'','cep'=>'','logradouro'=>'','numero'=>'','bairro'=>'','cidade'=>'','uf'=>'',
    'mostrar_endereco'=>0,'latitude'=>'','longitude'=>'','caracteristicas'=>null,'destaque'=>0);

if ($id) {
    $st = db()->prepare('SELECT * FROM imoveis WHERE id = ?');
    $st->execute(array($id));
    $row = $st->fetch();
    if (!$row) { header('Location: imoveis.php'); exit; }
    $im = $row;
}
// se houve erro de validação no POST, mantém o que o usuário digitou
if ($erro && $_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($im as $k => $v) if (isset($_POST[$k])) $im[$k] = $_POST[$k];
    $im['caracteristicas'] = json_encode((array) ($_POST['caracteristicas'] ?? array()));
    $im['mostrar_endereco'] = !empty($_POST['mostrar_endereco']) ? 1 : 0;
    $im['destaque'] = !empty($_POST['destaque']) ? 1 : 0;
}

$fotos = $id ? fotos_do_imovel($id) : array();
$selCaracs = caracteristicas_do_imovel($im);
$mapaCaracs = caracteristicas_mapa();
$flash = flash_get();

layout_head($id ? 'Editar imóvel' : 'Novo imóvel', true);
?>
<div class="bar">
  <div><h1><?php echo $id ? 'Editar imóvel' : 'Novo imóvel'; ?></h1>
    <p class="sub" style="margin:0"><?php echo $id ? e($im['referencia'] ?: ('#' . $id)) : 'Preencha os dados e salve para poder anexar fotos.'; ?></p></div>
  <a class="btn ghost" href="imoveis.php">← Voltar</a>
</div>

<?php if ($flash): ?><div class="ok-msg"><?php echo e($flash['texto']); ?></div><?php endif; ?>
<?php if ($erro): ?><div class="erro"><?php echo e($erro); ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data">
  <?php echo campo_csrf(); ?>
  <input type="hidden" name="acao" value="salvar">
  <input type="hidden" name="id" value="<?php echo (int) $id; ?>">

  <div class="card">
    <h2>Dados principais</h2>
    <div class="field"><label>Título *</label><input name="titulo" required value="<?php echo e($im['titulo']); ?>" placeholder="Ex.: Casa térrea com quintal no Anália Franco"></div>
    <div class="row">
      <div><label>Referência</label><input name="referencia" value="<?php echo e($im['referencia']); ?>" placeholder="(automático se vazio)"></div>
      <div><label>Finalidade</label>
        <select name="finalidade">
          <option value="venda" <?php if ($im['finalidade']==='venda') echo 'selected'; ?>>Venda</option>
          <option value="aluguel" <?php if ($im['finalidade']==='aluguel') echo 'selected'; ?>>Aluguel</option>
        </select></div>
      <div><label>Tipo</label>
        <select name="tipo">
          <?php foreach ($TIPOS as $t): ?><option value="<?php echo $t; ?>" <?php if ($im['tipo']===$t) echo 'selected'; ?>><?php echo e(tipo_label($t)); ?></option><?php endforeach; ?>
        </select></div>
      <div><label>Status</label>
        <select name="status">
          <?php foreach ($STATUSES as $s): ?><option value="<?php echo $s; ?>" <?php if ($im['status']===$s) echo 'selected'; ?>><?php echo e(status_label($s)); ?></option><?php endforeach; ?>
        </select></div>
    </div>
    <div class="row">
      <div><label>Preço (R$)</label><input name="preco" value="<?php echo e(num_str($im['preco'])); ?>" placeholder="Ex.: 1290000"></div>
      <div><label>Condomínio (R$)</label><input name="condominio" value="<?php echo e(num_str($im['condominio'])); ?>"></div>
      <div><label>IPTU (R$/ano)</label><input name="iptu" value="<?php echo e(num_str($im['iptu'])); ?>"></div>
    </div>
  </div>

  <div class="card">
    <h2>Detalhes</h2>
    <div class="row">
      <div><label>Dormitórios</label><input type="number" min="0" name="dormitorios" value="<?php echo (int) $im['dormitorios']; ?>"></div>
      <div><label>Suítes</label><input type="number" min="0" name="suites" value="<?php echo (int) $im['suites']; ?>"></div>
      <div><label>Banheiros</label><input type="number" min="0" name="banheiros" value="<?php echo (int) $im['banheiros']; ?>"></div>
      <div><label>Vagas</label><input type="number" min="0" name="vagas" value="<?php echo (int) $im['vagas']; ?>"></div>
    </div>
    <div class="row">
      <div><label>Área útil (m²)</label><input name="area_util" value="<?php echo e(num_str($im['area_util'])); ?>"></div>
      <div><label>Área total (m²)</label><input name="area_total" value="<?php echo e(num_str($im['area_total'])); ?>"></div>
    </div>
    <div class="field"><label>Descrição</label><textarea name="descricao" placeholder="Descreva o imóvel, diferenciais, região..."><?php echo e($im['descricao']); ?></textarea></div>
    <label>Características</label>
    <div class="checks">
      <?php foreach ($mapaCaracs as $chave => $rotulo): ?>
        <label><input type="checkbox" name="caracteristicas[]" value="<?php echo e($chave); ?>" <?php if (in_array($chave, $selCaracs, true)) echo 'checked'; ?>> <?php echo e($rotulo); ?></label>
      <?php endforeach; ?>
    </div>
  </div>

  <div class="card">
    <h2>Localização</h2>
    <div class="row">
      <div style="flex:2"><label>Bairro</label><input name="bairro" value="<?php echo e($im['bairro']); ?>"></div>
      <div style="flex:2"><label>Cidade</label><input name="cidade" value="<?php echo e($im['cidade']); ?>"></div>
      <div style="max-width:90px"><label>UF</label><input name="uf" maxlength="2" value="<?php echo e($im['uf']); ?>"></div>
    </div>
    <div class="row">
      <div><label>CEP</label><input name="cep" value="<?php echo e($im['cep']); ?>"></div>
      <div style="flex:2"><label>Logradouro</label><input name="logradouro" value="<?php echo e($im['logradouro']); ?>"></div>
      <div style="max-width:120px"><label>Número</label><input name="numero" value="<?php echo e($im['numero']); ?>"></div>
    </div>
    <div class="row">
      <div><label>Latitude</label><input name="latitude" value="<?php echo e(num_str($im['latitude'])); ?>" placeholder="-23.55"></div>
      <div><label>Longitude</label><input name="longitude" value="<?php echo e(num_str($im['longitude'])); ?>" placeholder="-46.63"></div>
    </div>
    <div class="checks" style="grid-template-columns:repeat(auto-fill,minmax(220px,1fr))">
      <label><input type="checkbox" name="mostrar_endereco" value="1" <?php if ($im['mostrar_endereco']) echo 'checked'; ?>> Mostrar endereço completo no site</label>
      <label><input type="checkbox" name="destaque" value="1" <?php if ($im['destaque']) echo 'checked'; ?>> Destacar na página inicial</label>
    </div>
  </div>

  <div class="card">
    <h2>Adicionar fotos</h2>
    <p class="muted" style="margin-bottom:12px">JPG, PNG ou WebP, até 8 MB cada. As imagens são redimensionadas automaticamente e você pode enviá-las já neste cadastro.</p>
    <input type="file" name="fotos[]" accept="image/*" multiple>
  </div>

  <button class="btn" style="font-size:15px;padding:13px 26px">Salvar imóvel</button>
</form>

<?php if ($id && $fotos): ?>
<div class="card" style="margin-top:24px">
  <h2>Fotos do imóvel (<?php echo count($fotos); ?>)</h2>
  <p class="muted" style="margin-bottom:16px">Defina a capa, ajuste a ordem (menor aparece primeiro) e a legenda.</p>
  <div class="fotos-grid">
    <?php foreach ($fotos as $f): ?>
      <div class="foto-item <?php echo $f['capa'] ? 'iscapa' : ''; ?>">
        <img src="<?php echo e(foto_url($id, $f['arquivo'], true, '../')); ?>" alt="">
        <div class="meta">
          <form method="post" style="margin:0">
            <?php echo campo_csrf(); ?>
            <input type="hidden" name="acao" value="salvar_foto"><input type="hidden" name="id" value="<?php echo (int) $id; ?>"><input type="hidden" name="foto_id" value="<?php echo (int) $f['id']; ?>">
            <div style="display:flex;gap:6px;margin-bottom:6px">
              <input type="number" name="ordem" value="<?php echo (int) $f['ordem']; ?>" style="max-width:70px" title="Ordem">
              <input type="text" name="legenda" value="<?php echo e($f['legenda']); ?>" placeholder="Legenda">
            </div>
            <button class="btn sm ghost btn-block" style="width:100%;justify-content:center">Salvar</button>
          </form>
          <div class="acoes" style="margin-top:6px">
            <?php if (!$f['capa']): ?>
            <form method="post" style="margin:0;flex:1">
              <?php echo campo_csrf(); ?>
              <input type="hidden" name="acao" value="capa"><input type="hidden" name="id" value="<?php echo (int) $id; ?>"><input type="hidden" name="foto_id" value="<?php echo (int) $f['id']; ?>">
              <button class="btn sm warn btn-block" style="width:100%;justify-content:center">★ Capa</button>
            </form>
            <?php else: ?><span class="tag destaque" style="flex:1;text-align:center">Capa</span><?php endif; ?>
            <form method="post" style="margin:0" onsubmit="return confirm('Excluir esta foto?')">
              <?php echo campo_csrf(); ?>
              <input type="hidden" name="acao" value="excluir_foto"><input type="hidden" name="id" value="<?php echo (int) $id; ?>"><input type="hidden" name="foto_id" value="<?php echo (int) $f['id']; ?>">
              <button class="btn sm danger">✕</button>
            </form>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<?php layout_foot(); ?>
