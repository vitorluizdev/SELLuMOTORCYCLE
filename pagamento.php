<?php 
require_once 'funcoes.php';
if (!logado()) redirect('login.php');

$anuncio_id = $_GET['id'];

if ($_POST) {
    if (processarPagamento($anuncio_id)) {
        redirect('index.php?sucesso=1');
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Pagamento - <?= SITE_NOME ?></title>
    <link rel="stylesheet" href="css/estilo.css">
</head>
<body>
    <div class="form-container">
        <h2>Pagamento</h2>
        <div class="pagamento-info">
            <h3>Resumo</h3>
            <p>Publicação de anúncio: €<?= PRECO_ANUNCIO ?></p>
            <p><strong>Total: €<?= PRECO_ANUNCIO ?></strong></p>
        </div>
        
        <form method="POST">
            <h4>Método de Pagamento (Fictício)</h4>
            
            <label><input type="radio" name="metodo" value="cartao" required> Cartão de Crédito</label>
            <label><input type="radio" name="metodo" value="mbway"> MB WAY</label>
            <label><input type="radio" name="metodo" value="paypal"> PayPal</label>
            
            <div class="cartao-dados">
                <input type="text" placeholder="1234 5678 9012 3456" maxlength="19">
                <div class="row">
                    <input type="text" placeholder="MM/AA" maxlength="5">
                    <input type="text" placeholder="CVV" maxlength="3">
                </div>
            </div>
            
            <button type="submit" class="btn success">Pagar e Publicar</button>
        </form>
        
        <p class="nota"><strong>NOTA:</strong> Pagamento fictício para fins académicos!</p>
    </div>
</body>
</html>