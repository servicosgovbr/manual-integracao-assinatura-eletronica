Iniciando a Integração
================================

Solicitação de Configuração
++++++++++++++++++++++++++++++++

É premissa de toda aplicação que irá consumir os serviços da API do assinador estar integrada ao sistema `Login Único`_. No entanto, a autorização de acesso utilizado pela assinatura é condicionada ao processo de autorização explícita do usuário (Conforme `Lei n° 14.063`_ Art.4º). O usuário tem que autorizar o ITI a deixar a aplicação assinar em nome do usuário e isso é realizado durante o fluxo de autorização OAuth da API de assinatura, por esse motivo é que a liberação de acesso para emissão do certificado implica a geração de uma requisição ao servidor OAuth que controla os recursos desta API. 

Para utilização da API de assinatura digital gov.br, há necessidade de liberar os ambientes para que a aplicação cliente possa utilizar. A liberação do ambiente de homologação ocorre por meio de envio das informações listadas abaixo: 

1. **URL de retorno para cadastramento da aplicação**
2. **Chave PGP** - A chave PGP é solicitada para transmissão das credenciais de autenticação de forma segura, isto é, criptografada. Informações sobre como gerar chaves PGP e envio da chave pública, podem ser verificadas em `Tutorial para geração de chaves PGP <https://github.com/servicosgovbr/manual-integracao-assinatura-eletronica/raw/main/arquivos/Tutorial%20para%20gera%C3%A7%C3%A3o%20chave%20PGP.pdf>`_ 
3. **Endereço de e-mail do destinatário** para recebimento das credenciais; 
4. **Volumetria anual estimada da quantidade de documentos que serão assinados**. 

Essas informações deverão ser encaminhadas, para o e-mail **int-assinatura-govbr@economia.gov.br** da Secretaria de Governança Digital (SGD) do Ministério da Economia (ME), por e-mail de um representante legal do órgão ou entidade dona do serviço a ser integrado. A liberação do ambiente de produção ocorrerá somente após a homologação final validada com os integrantes da SGD/ME. 

Orientações para testes em ambiente de homologação 
+++++++++++++++++++++++++++++++++++++++++++++++++++

De Acordo com a portaria `SEDGGME Nº 2.154/2021`_ as identidades digitais da plataforma Gov.br são classificadas em três tipos: Bronze, Prata e Ouro. A identidade bronze permite ao usuário somente a realização de assinaturas simples. Nesta plataforma para realizar uma assinatura avançada, seja qual for o ambiente, o usuário deve possuir identidade digital prata ou ouro. Caso ele não possua este nível de identidade, a aplicação cliente deverá comunicar que ele precisa adquirir e que após a obtenção deverá acessar novamente a aplicação para realizar a assinatura. O link que direcionará para área logada do Gov.br deverá estar presente na mensagem da aplicação. Segue abaixo um exemplo de mensagem e o link correto: 

"Prezado solicitante, para realizar a(s) assinatura(s) é necessário que sua conta na plataforma Gov.Br seja "Prata" ou "Ouro". 
Saiba como adquirir a conta "Ouro" ou "Prata” acessando o link https://contas.acesso.gov.br/privacidade."

Ao realizar testes, no ambiente de homologação, o testador deve criar uma conta seguindo os passos deste `Tutorial conta ID prata <https://github.com/servicosgovbr/manual-integracao-assinatura-eletronica/raw/main/arquivos/Tutorial%20-%20ID%20Prata.pdf>`_. Obs.: No ambiente de testes é possível criar conta para qualquer CPF (gerador de CPF: https://www.4devs.com.br/gerador_de_cpf). 

**Importante**: Somente os documentos assinados em ambiente de **PRODUÇÃO** podem ser validados no Verificador de Conformidade do ITI (https://verificador.iti.br/). Documentos assinados digitalmente em ambiente de **HOMOLOGAÇÃO** podem ser verificados em https://govbr-assina.homologacao.ufsc.br/. 

API de assinatura digital gov.br
++++++++++++++++++++++++++++++++

Este documento detalha a estrutura da API REST para assinatura digital usando certificados avançados gov.br.

A API adota o uso do protocolo OAuth 2.0 para autorização de acesso e o protocolo HTTP para acesso aos endpoints. Deste modo, o uso da API envolve duas etapas:

1. Geração do token de acesso OAuth (Access Token)

2. Acesso ao serviço de assinatura

Geração do Access Token
+++++++++++++++++++++++

Para geração do Access Token é necessário redirecionar o navegador do usuário para o endereço de autorização do servidor OAuth, a fim de obter seu consentimento para o uso de seu certificado para assinatura. Nesse processo, a aplicação deve usar credenciais previamente autorizadas no servidor OAuth. As seguintes credencias podem ser usadas para testes:

.. code-block:: console

		Servidor OAuth = https://sistemas.homologacao.ufsc.br/govbr/oauth2.0
		Client ID= devLocal
		Secret = younIrtyij3
		URI de redirecionamento = http://127.0.0.1:*/**

As credenciais para Client ID “devLocal” estão configuradas no servidor OAuth para aceitar qualquer aplicação executando localmente (host 127.0.0.1, qualquer porta, qualquer caminho). Aplicações remotas não poderão usar essas credenciais de teste.

A URL usada para redirecionar o usuário para o formulário de autorização, conforme a especificação do OAuth 2.0, é a seguinte:

.. code-block:: console

		https://<Servidor OAuth>/authorize?response_type=code&redirect_uri=<URI de redirecionamento>&scope=sign&client_id=<clientId>

Nesse endereço, o servidor OAuth faz a autenticação e pede a autorização expressa do usuário para acessar seu certificado para assinatura. Neste instante será pedido um código de autorização a ser enviado por SMS. **IMPORTANTE: EM HOMOLOGAÇÃO**, NÃO SERÁ ENVIADO SMS, DEVE-SE USAR O CÓDIGO **12345**.

Após a autorização, o servidor OAuth redireciona o usuário para o endereço <URI de redirecionamento> especificado e passa, como um parâmetro de query, o atributo Code. O <URI de redirecionamento> deve ser um endpoint da aplicação correspondente ao padrão autorizado no servidor OAuth, e capaz de receber e tratar o parâmetro “code”. Este atributo deve ser usado na fase seguinte do protocolo OAuth, pela aplicação, para pedir um Access Token ao servidor OAuth, com a seguinte requisição HTTP com método POST:

.. code-block:: console

		https://<Servidor OAuth>/token?code=<code>&client_id=<clientId>&grant_type=authorization_code&client_secret=<secret>&redirect_uri=<URI de redirecionamento>

O <URI de redirecionamento> deve ser exatamente o mesmo valor passado na requisição “authorize” anterior. O servidor OAuth retornará um objeto JSON contendo o Access Token, que deve ser usado nas requisições subsequentes aos endpoints do serviço.

**Importante**: O servidor OAuth de homologação está delegando a autenticação ao ambiente de **Staging** do gov.br

**Importante**: O Access Token gerado autoriza o uso da chave privada do cidadão para a confecção de **uma** única assinatura eletrônica avançada. O token deve ser usado em até 10 minutos. O tempo de validade do token poderá ser modificado no futuro à discrição do ITI.

Obtenção do certificado do usuário
++++++++++++++++++++++++++++++++++

Para obtenção do certificado do usuário deve-se fazer uma requisição HTTP Get para o seguinte end-point:

.. code-block:: console

		https://govbr-uws.homologacao.ufsc.br/CloudCertService/certificadoPublico 

Deve-se enviar o cabeçalho Authorization  com o tipo de autorização Bearer e o Access Token obtido anteriormente. Exemplo de requisição:

.. code-block:: console

		GET /CloudCertService/certificadoPublico HTTP/1.1
		Host: govbr-uws.homologacao.ufsc.br 
		Authorization: Bearer <Access token>

Será retornado o certificado digital em formato PEM na resposta.

Realização da assinatura digital Raw de um HASH SHA-256
+++++++++++++++++++++++++++++++++++++++++++++++++++++++

Para assinar digitalmente um HASH SHA-256 usando a chave privada do usuário, deve-se fazer uma requisição HTTP POST para o seguinte end-point:

.. code-block:: console

		https://govbr-uws.homologacao.ufsc.br/CloudCertService/assinarRaw

Deve-se enviar o cabeçalho Authorization com o tipo de autorização Bearer e o Access Token obtido anteriormente. Exemplo de requisição:

.. code-block:: console

		POST /CloudCertService/assinarRaw HTTP/1.1
		Host: govbr-uws.homologacao.ufsc.br
		Content-Type: application/json	
		Authorization: Bearer <Access token>
		Content-Type: application/json

		{"hashBase64":"<Hash SHA256 codificado em Base64>"}


Será retornada a assinatura digital SHA256-RSA codificada em Base64 na resposta.

Realização da assinatura digital de um HASH SHA-256 em PKCS#7
+++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

Para gerar um pacote PKCS#7 contendo a assinatura digital de um HASH SHA-256 usando a chave privada do usuário, deve-se fazer uma requisição HTTP POST para o seguinte end-point:

.. code-block:: console

		https://govbr-uws.homologacao.ufsc.br/CloudCertService/assinarPKCS7

Deve-se enviar o cabeçalho Authorization com o tipo de autorização Bearer e o Access Token obtido anteriormente. Exemplo de requisição:

.. code-block:: console

		POST /CloudCertService/assinarPKCS7 HTTP/1.1
		Host: govbr-uws.homologacao.ufsc.br
		Content-Type: application/json	
		Authorization: Bearer <Access token>
		Content-Type: application/json

		{"hashBase64":"<Hash SHA256 codificado em Base64>"}

Será retornado um arquivo contendo o pacote PKCS#7 com a assinatura digital do hash SHA256-RSA e com o certificado público do usuário. O arquivo retornado pode ser validado em https://govbr-verifier.homologacao.ufsc.br.

Exemplo de aplicação
++++++++++++++++++++

Logo abaixo, encontra-se um pequeno exemplo PHP para prova de conceito.

`Download Exemplo PHP <https://github.com/servicosgovbr/manual-integracao-assinatura-eletronica/raw/main/downloadFiles/exemploApiPhp.zip>`_

Este exemplo é composto por 3 arquivos:

1. index.php -  Formulário para upload de um arquivo
2. upload.php - Script para recepção de arquivo e cálculo de seu hash SHA256. O Resultado do SHA256 é armazenado na sessão do usuário.
3. assinar.php - Implementação do handshake OAuth, assim como a utilização dos dois endpoints acima. Como resultado, uma página conforme a figura abaixo será apresentada, mostrando o certificado emitido para o usuário autenticado e a assinatura.


.. image:: images/image.png


Para executar o exemplo, é possível utilizar Docker com o comando abaixo:

.. code-block:: console
	
		docker-compose up -d

e acessar o endereço http://127.0.0.1:8080

.. |site externo| image:: images/site-ext.gif
.. _`codificador para Base64`: https://www.base64decode.org/
.. _`Plano de Integração`: arquivos/Modelo_PlanodeIntegracao_LOGINUNICO_final.doc
.. _`OpenID Connect`: https://openid.net/specs/openid-connect-core-1_0.html#TokenResponse
.. _`auth 2.0 Redirection Endpoint`: https://tools.ietf.org/html/rfc6749#section-3.1.2
.. _`Exemplos de Integração`: exemplointegracao.html
.. _`Design System do Governo Federal`: http://dsgov.estaleiro.serpro.gov.br/ds/componentes/button
.. _`Resultado Esperado do Acesso ao Serviço de Confiabilidade Cadastral (Selos)`: iniciarintegracao.html#resultado-esperado-do-acesso-ao-servico-de-confiabilidade-cadastral-selos
.. _`Resultado Esperado do Acesso ao Serviço de Confiabilidade Cadastral (Categorias)` : iniciarintegracao.html#resultado-esperado-do-acesso-ao-servico-de-confiabilidade-cadastral-categorias
.. _`Documento verificar Código de Compensação dos Bancos` : arquivos/TabelaBacen.pdf
.. _`Login Único`: https://manual-roteiro-integracao-login-unico.servicos.gov.br/pt/stable/index.html
.. _`Lei n° 14.063`: http://www.planalto.gov.br/ccivil_03/_ato2019-2022/2020/lei/L14063.htm
.. _`SEDGGME Nº 2.154/2021`: https://www.in.gov.br/web/dou/-/portaria-sedggme-n-2.154-de-23-de-fevereiro-de-2021-304916270