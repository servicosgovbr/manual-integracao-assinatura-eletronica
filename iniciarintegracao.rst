Iniciando a Integração
================================

Solicitação de Configuração
++++++++++++++++++++++++++++++++

É premissa de toda aplicação que irá consumir os serviços da API do assinador estar integrada ao sistema `Login Único`_. No entanto, a autorização de acesso utilizado pela assinatura é condicionada ao processo de autorização explícita do usuário (Conforme `Lei n° 14.063`_ Art.4º). O usuário tem que autorizar o ITI a deixar a aplicação assinar em nome do usuário e isso é realizado durante o fluxo de autorização OAuth da API de assinatura, por esse motivo é que a liberação de acesso para emissão do certificado implica a geração de uma requisição ao servidor OAuth que controla os recursos desta API. 

Para utilização da API de assinatura digital gov.br, há necessidade de liberar os ambientes para que a aplicação cliente possa utilizar. A liberação do ambiente de homologação ocorre por meio de envio das informações listadas abaixo: 

1. **URL de retorno para cadastramento da aplicação**
2. **Chave PGP** - A chave PGP é solicitada para transmissão das credenciais de autenticação de forma segura, isto é, criptografada. Informações sobre como gerar chaves PGP e envio da chave pública, podem ser verificadas no último tópico deste roteiro.
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

Como criar um par de chaves PGP
+++++++++++++++++++++++++++++++

**GnuPG para Windows** 

Faça o download do aplicativo Gpg4win em: https://gpg4win.org/download.html
O Gpg4win é um pacote de instalação para qualquer versão do Windows, que inclui o software de criptografia GnuPG. Siga abaixo as instruções detalhadas de como gerar um par de chaves PGP:

1. Após o download, execute a instalação e deixe os seguintes componentes marcados conforme imagem abaixo:

.. image:: images/pgp1.png

2. Concluída a instalação, execute o **Kleopatra** para a criação do par de chaves. Kleopatra é uma ferramenta do KDE para gerenciamento de certificados X.509, chaves PGP e também para gerenciamento de certificados de servidores. A janela principal deverá se parecer com a seguinte:

.. image:: images/pgp2.png

3. Para criar novo par de chave, vá até o item do Menu **Arquivo** → **Novo Par de chaves...** selecione **Criar um par de chaves OpenPGP pessoal**. Na tela seguinte informe os detalhes **Nome** e **Email**, marque a opção para proteger a chave com senha e clique em **Configurações avançadas...**

4. Escolha as opções para tamanho das chaves e defina uma data de validade para o par de chaves. Esta data pode ser alterada depois. Após confirmação da tela abaixo, abrirá uma janela para informar a senha. O ideal é colocar uma senha forte, que deve conter pelo menos 8 caracteres, 1 digito ou caractere especial.

.. image:: images/pgp3.png

5. Após criado o par de chave, você pode enviar sua chave pública por email clicando em **Enviar chave pública por e-mail...** ou pode clicar em **Terminar** e exportar a sua chave pública para enviá-la por email posteriormente. Para exportar sua chave pública e enviá-la em anexo ao seu email, clique com
botão direito na sua chave depois em **Exportar...**

**GnuPG para Linux** 

Praticamente todas as distribuições do Linux trazem o GnuPG instalado e para criar um par de chaves pública e privada em nome do utilizador 'Fulano de Tal', por exemplo, abra o terminal e execute o comando abaixo. Se não forem especificados os parâmetros adicionais, o tipo e o tamanho da chave serão RSA e 3072 bits, respectivamente. Será perguntado uma frase para a senha (frase secreta, memorize-a), basta responder de acordo com o que será pedido.

.. code-block:: console

		$ gpg --gen-key
		
		gpg (GnuPG) 2.2.19; Copyright (C) 2019 Free Software Foundation, Inc.
		This is free software: you are free to change and redistribute it.
		There is NO WARRANTY, to the extent permitted by law.
		gpg: directory '/home/user/.gnupg' created
		gpg: keybox '/home/user/.gnupg/pubring.kbx' created
		Note: Use "gpg --full-generate-key" for a full featured key generation dialog.

	    O GnuPG precisa construir uma ID de usuário para identificar sua chave.

		Nome completo: Fulano de Tal
		Endereço de correio eletrônico: fulanodetal@email.com
		Você selecionou este identificador de usuário:
		"Fulano de Tal <fulanodetal@email.com>"
		Change (N)ame, (E)mail, or (O)kay/(Q)uit? O

		Precisamos gerar muitos bytes aleatórios. É uma boa ideia realizar outra atividade (digitar no teclado, mover o mouse, usar os discos)
        durante a geração dos números primos; isso dá ao gerador de números aleatórios uma chance melhor de conseguir entropia suficiente.

		gpg: /home/user/.gnupg/trustdb.gpg: banco de dados de confiabilidade criado
        gpg: chave D5882F501CC722AA marcada como plenamente confiável
        gpg: directory '/home/user/.gnupg/openpgp-revocs.d' created
        gpg: revocation certificate stored as '/home/user/.gnupg/openpgprevocs.d/269C3D6B65B150A9B349170D5882F501CC722AA.rev'

		Chaves pública e privada criadas e assinadas.

		pub rsa3072 2021-04-30 [SC] [expira: 2023-04-30] 269C3D6B65B150A9B349170D5882F501CC722AA
        uid Fulano de Tal <fulanodetal@email.com>
        sub rsa3072 2021-04-30 [E] [expira: 2023-04-30]
		
Para enviar um documento ou um e-mail cifrado com sua chave, é necessário que a pessoa tenha a sua chave pública.Partindo do ponto que a pessoa fez um pedido da sua chave pública, então é necessário criar um arquivo
com a chave e passar o arquivo para o solicitante (por exemplo, podemos passar pelo e-mail). Execute o comando abaixo no terminal do Linux para exportar a sua chave para o arquivo MinhaChave.asc.

.. code-block:: console
	
		$gpg --export 269C3D6B65B150A9B449170D5882F501CC722AA> MinhaChave.asc

Onde "269C3D6B65B150A9B349170D5882F501CC722AA" é o ID da chave (da chave que criamos aqui no exemplo, substitua pelo seu ID) e **MinhaChave.asc** é o nome do arquivo onde será gravada a chave (pode ser outro nome).
Agora basta enviar o arquivo com a chave pública para a pessoa e então ela poderá criptografar um e-mail ou um documento com a sua chave pública. Se foi criptografado com a sua chave pública, somente a sua chave privada será capaz de decodificar o documento (e a frase secreta de sua chave será requisitada).


Para **encriptar** um documento com a chave pública de ‘Fulano de Tal’ basta seguir os comandos abaixo, substituindo **NomeArquivo** pelo nome do arquivo a ser criptografado. Um arquivo com nome **NomeArquivo.gpg** será criado na pasta atual. Este arquivo com dados criptografados só poderá ser decifrado pela chave privada de ‘Fulano de Tal’.

.. code-block:: console
	
		$ gpg -e NomeArquivo

		Você não especificou um ID de usuário. (pode-se usar "-r")
		Recipientes atuais:
		Entre com o ID do usuário. Final com uma linha vazia: Fulano de Tal
		Recipientes atuais: rsa3072/4628820328759F85 2021-04-30 "Fulano de Tal <fulanodetal@email.com>"
		Entre com o ID do usuário. Final com uma linha vazia: <Enter>

Para **decifrar** um documento que foi criptografado por Fulano de Tal basta seguir os passos abaixo, substituindo **NomeArquivo.gpg** pelo nome do arquivo cifrado. Será solicitada a frase secreta da chave privada de Fulano de tal, basta inseri-la. Um arquivo com nome **ArquivoTextoClaro** será criado na mesma pasta. Este arquivo contêm os dados decifrados.		

.. code-block:: console
	
		$ gpg -d NomeArquivo.gpg > ArquivoTextoClaro
		gpg: criptografado com 3072-bit RSA chave, ID 4628820328759F85, criado 2021-04-24 "Fulano de Tal <fulanodetal@email.com>"






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