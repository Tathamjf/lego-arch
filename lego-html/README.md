# LEGO Architecture Store — versão HTML com PHP integrado

E-commerce básico com **gerenciamento de contas (login)**, **carrinho** e **estoque**.
PHP puro (PDO) + MySQL, para rodar no **UsbWebServer** ou no **XAMPP**.

## Como rodar

### UsbWebServer 8.6
1. Copie esta pasta para a pasta `root` do UsbWebServer, como `root/lego/`.
2. Abra o UsbWebServer e inicie Apache e MySQL.
3. O `config.php` já vem com as portas/senha padrão do UsbWebServer (MySQL na porta 3307, usuário `root`, senha `usbw`).
4. Acesse `http://localhost:8080/lego/` (a porta do Apache aparece na janela do UsbWebServer).

### XAMPP
1. Copie esta pasta para `C:\xampp\htdocs\lego\`.
2. Inicie Apache e MySQL no painel do XAMPP.
3. No `config.php`, troque: `DB_PORT` para `3306` e `DB_PASS` para `''` (vazio).
4. Acesse `http://localhost/lego/`.

### Banco de dados
No phpMyAdmin, importe nesta ordem (se ainda não importou):
1. `sql/schema.sql` — cria o banco `lego_architecture_store` e as tabelas
2. `sql/seed.sql` — categorias e produtos de exemplo

### Usuários iniciais
Abra **uma vez** `http://.../lego/instalar.html`. Ele cria:
- `admin@lego.local` / `Admin@123` (administrador)
- `cliente@lego.local` / `Cliente@123` (cliente de teste)

Depois **apague o `instalar.html`** e troque a senha do admin em "Minha conta".

## O que tem

| Requisito | Onde |
|---|---|
| Cadastro e login | `registro.html`, `login.html`, `logout.html` |
| Gerenciamento de conta | `conta.html` (nome e senha), `admin/usuarios.html` (ativar/desativar, perfil) |
| Catálogo | `index.html` (busca, categoria, ordenação), `produto.html` |
| Carrinho | `carrinho.html` (adicionar, alterar, remover, esvaziar; limitado ao estoque) |
| Checkout | `checkout.html` (transação com trava de estoque) |
| Pedidos | `pedidos.html` (cliente, com cancelamento), `admin/pedidos.html` (status) |
| Estoque | `admin/estoque.html` (entrada/ajuste + histórico), baixa automática na compra, devolução no cancelamento |
| Produtos | `admin/index.html`, `admin/produto_form.html` |

## Segurança já incluída
- Senhas com `password_hash` / `password_verify` (bcrypt)
- Consultas com PDO + prepared statements (sem SQL injection)
- Token CSRF em todos os formulários POST
- Escape de saída (`e()`) contra XSS
- `session_regenerate_id` no login
- Páginas de admin protegidas por `require_admin()`

## Compatibilidade
Se o PHP não tiver `pdo_mysql`, o projeto usa `mysqli` automaticamente (includes/mysqli_compat.php). Código escrito para **PHP 5.6+** e **MySQL 5.6+** (sem `??`, sem tipos escalares). Precisa das extensões `pdo_mysql` e `mbstring` (vêm ativas no XAMPP; no UsbWebServer, confira em `phpinfo()`).

## Diagnóstico
Abra `versao.html` (em localhost) para ver a versão do projeto, do PHP, as extensões e o teste do banco. Apague depois de usar.

## PHP antigo
Se o PHP for anterior ao 5.5 (erro `Call to undefined function password_hash()`), o arquivo `includes/compat.php` cria as funções que faltam. Mesmo assim, o ideal é usar PHP 7.4 ou mais novo (XAMPP atual).

## Páginas .html que executam PHP
As páginas têm extensão `.html`, mas o PHP roda dentro delas. Isso é feito pelo arquivo `.htaccess` (linha `AddType application/x-httpd-php .html`).
Os arquivos de apoio (`config.php` e a pasta `includes/`) continuam `.php` e ficam bloqueados para acesso direto.

**Primeiro teste:** abra `http://localhost:PORTA/lego/teste-php.html`. Deve aparecer a versão do PHP.

**Se aparecer o código `<?php ...` na tela (ou o número não aparecer):** o Apache ignorou o `.htaccess`. Abra o `httpd.conf` do UsbWebserver (pasta de configurações do Apache), e:
1. procure o bloco `<Directory ...>` da pasta `root` e confirme `AllowOverride All`; **ou**
2. adicione a linha `AddType application/x-httpd-php .html` perto das outras `AddType`.
Reinicie o Apache e teste de novo.

A tela inicial é `index.html`. Para criar os usuários: `instalar.html`. Diagnóstico: `versao.html`.
