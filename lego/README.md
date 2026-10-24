# LEGO Architecture Store — versão PHP + MySQL

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
Abra **uma vez** `http://.../lego/instalar.php`. Ele cria:
- `admin@lego.local` / `Admin@123` (administrador)
- `cliente@lego.local` / `Cliente@123` (cliente de teste)

Depois **apague o `instalar.php`** e troque a senha do admin em "Minha conta".

## O que tem

| Requisito | Onde |
|---|---|
| Cadastro e login | `registro.php`, `login.php`, `logout.php` |
| Gerenciamento de conta | `conta.php` (nome e senha), `admin/usuarios.php` (ativar/desativar, perfil) |
| Catálogo | `index.php` (busca, categoria, ordenação), `produto.php` |
| Carrinho | `carrinho.php` (adicionar, alterar, remover, esvaziar; limitado ao estoque) |
| Checkout | `checkout.php` (transação com trava de estoque) |
| Pedidos | `pedidos.php` (cliente, com cancelamento), `admin/pedidos.php` (status) |
| Estoque | `admin/estoque.php` (entrada/ajuste + histórico), baixa automática na compra, devolução no cancelamento |
| Produtos | `admin/index.php`, `admin/produto_form.php` |

## Segurança já incluída
- Senhas com `password_hash` / `password_verify` (bcrypt)
- Consultas com PDO + prepared statements (sem SQL injection)
- Token CSRF em todos os formulários POST
- Escape de saída (`e()`) contra XSS
- `session_regenerate_id` no login
- Páginas de admin protegidas por `require_admin()`

## Compatibilidade
Se o PHP não tiver `pdo_mysql`, o projeto usa `mysqli` automaticamente (includes/mysqli_compat.php). Código escrito para **PHP 5.6+** e **MySQL 5.6+** (sem `??`, sem tipos escalares). Precisa das extensões `pdo_mysql` e `mbstring` (vêm ativas no XAMPP; no UsbWebServer, confira em `phpinfo()`).
