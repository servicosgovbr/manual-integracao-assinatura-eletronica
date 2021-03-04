<?php
session_start();

################################################
# Conf desenv govbr
$redirect_uri = "http://127.0.0.1:8080/assinar.php";
$clientid = "devLocal";
$secret = "younIrtyij3";
$servidorOauth = "sistemas.homologacao.ufsc.br/govbr/oauth2.0"; #govbr
$servidorNuvemQualificada = "govbr-uws.homologacao.ufsc.br/CloudCertService";


################################################
# Inicio do handshake OAuth
$code = $_GET['code'];
if (!$code) {
  // 1. Pedir autorização ao usuário
  $authorizeUri = "https://$servidorOauth/authorize" .
                                        "?response_type=code" .
                                        "&redirect_uri=" . urlencode($redirect_uri) .
                                        "&scope=sign" .
                                        "&client_id=$clientid";

  header("Location: $authorizeUri"); /* Redirect browser */
  exit;
} else {
  // 2. Obter access token a partir do código de autorização
  $accessTokenUri = "https://$servidorOauth/token" .
                "?code=$code" .
                "&client_id=$clientid" .
                "&grant_type=authorization_code" .
                "&client_secret=$secret" .
                "&redirect_uri=" . urlencode($redirect_uri);

  $options = array(
      'http' => array(
          'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
          'method'  => 'POST'
      )
  );

  $context  = stream_context_create($options);
  $result = file_get_contents($accessTokenUri, false, $context);
  $response = json_decode($result);
  $AT = $response->access_token;
  ################################################
  # Início das operações criptográficas

  // 3. Fazer download do certificado público
  
  // hash a ser assinadado. Colocadano na sessão pelo upload.php
  $hash = $_SESSION['hash'];

  $certificateUri = "https://$servidorNuvemQualificada/certificadoPublico";
  
  $options = array(
      'http' => array(
          'header'  => "Authorization: Bearer $AT\r\n",
          'method'  => 'GET'
      )
  );

  $context  = stream_context_create($options);
  $certBase64 = file_get_contents($certificateUri, false, $context);

  // 4. Fazer a operação de criptográfica de assinatura
  $signingUri = "https://$servidorNuvemQualificada/assinarRaw";

  // Assume hash é SHA256
  $pacoteAssinatura = json_encode(array('hashBase64' => base64_encode($hash)));
  $options = array(
      'http' => array(
          'header'  => "Content-type: application/json\r\n" .
                       "Authorization: Bearer $AT\r\n",
          'method'  => 'POST',
          'content' => $pacoteAssinatura
      )
  );

  $context  = stream_context_create($options);
  $assinaturaBase64 = file_get_contents($signingUri, false, $context);
  
  ?>

  <h3>Certificado</h3>
  <div><?php echo $certBase64 ?></div>
  <h3>Assinatura</h3>
  <div><?php echo $assinaturaBase64 ?></div>

  <?php
}
?>
