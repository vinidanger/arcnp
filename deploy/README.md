# Painel (arcnp) — notas de deploy e arquitetura

Painel de gerenciamento de hospedagem (Laravel 12). Fala com o servidor de
hospedagem através de um Agent separado (`arcnp-agent`, outro repositório,
instalado em `/opt/arcnp-agent` na VPS de hospedagem) via HTTP assinado
(HMAC). Este repositório roda em `/var/www/arcnp` no servidor do Painel.

## Modelo de autenticação

**Não existe recuperação de senha por e-mail.** Isso é só um painel de
gerenciamento — a conta "de verdade" do cliente (faturas, cobrança etc.)
mora em outro sistema. Senha esquecida se resolve manualmente:
admin reseta a senha do cliente pela tela da hospedagem dele (SSH), ou o
próprio admin troca a sua em Perfil.

### Admin

Login por **usuário + senha** (`users.username` + `users.password`).
E-mail é opcional e não serve pra login — é só um campo de contato, se
quiser preencher. O único jeito de criar um admin hoje é via
`DatabaseSeeder`/tinker (não existe UI de "criar admin"); o admin já
criado pode trocar o próprio `username`/senha em Perfil.

```php
User::create([
    'name' => 'Fulano',
    'username' => 'fulano',   // é isso que ele digita no login
    'password' => 'algumasenha',
    'type' => 'admin',
    'status' => 'active',
    'email_verified_at' => now(),
]);
```

### Cliente

Login por **usuário da hospedagem + senha** (estilo cPanel/DirectAdmin) —
`hosting_accounts.linux_username` + `hosting_accounts.ssh_password`. É a
MESMA senha usada pra acesso SSH (ver `App\Domain\Hosting\Services\SshAccessService`):
gerada automaticamente já na criação da conta
(`HostingAccountProvisioningService::provision()`), mesmo com o acesso
SSH em si (`ssh_enabled`) continuando desligado por padrão — só a senha
existe desde já, o shell continua bloqueado até ser liberado
explicitamente.

Cliente troca a própria senha em **Perfil** (funciona a qualquer momento,
não depende do SSH estar ligado) — por baixo dos panos isso grava em
`ssh_password` e sincroniza com o Agent, não em `users.password`.
`users.password` para um cliente é só um valor aleatório gerado na
criação pra satisfazer a coluna do banco — nunca é usado, nunca é
mostrado.

`users.email`, pra cliente, é **só contato/referência**: não precisa
existir, não precisa ser único, dois clientes podem compartilhar o mesmo
e-mail sem problema (ex.: mesma agência gerenciando vários sites).

### 1 cliente = 1 hospedagem, sempre

Todo cliente (`users.type = 'client'`) tem no máximo uma
`HostingAccount` — travado em nível de banco (`unique(user_id)` em
`hosting_accounts`). Um cliente sem hospedagem ainda cai numa tela de
espera ao logar (`client.dashboard`), mas só consegue logar de verdade
depois que a hospedagem existe (é o `linux_username` dela que vira o
login).

Admin cria um cliente e é redirecionado direto pra criar a hospedagem
dele em seguida — as duas telas continuam separadas, mas encadeadas.

## Deploy

```bash
cd /var/www/arcnp
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
npm ci && npm run build   # só se algo em resources/ mudou
php artisan config:cache   # se usar cache de config em produção
php artisan route:cache    # idem
```

`composer install`/`composer dump-autoload` é obrigatório sempre que
`composer.json` ganhar uma entrada nova em `autoload.files` (ex.:
`app/Support/helpers.php`, que registra o helper global `status_label()`)
— sem isso, o autoloader antigo do servidor não sabe que o arquivo novo
existe, e qualquer chamada à função dá "Call to undefined function" em
produção mesmo com o código já deployado.

## Estrutura (visão rápida)

- `app/Domain/Hosting` — contas de hospedagem, DNS, e-mail, PHP, SSH,
  backups, apps, etc. (o grosso do produto).
- `app/Domain/Clients` — CRUD de clientes (admin).
- `app/Domain/Servers` — servidores pareados com o Agent, credenciais,
  jobs/auditoria de toda ação disparada.
- `app/Domain/Support` — sistema de chamados (tickets).
- `app/Domain/Api` — API REST versionada (`/api/v1`), autenticação via
  Sanctum, pra integração com outros sistemas (ex.: o sistema de
  faturamento criar hospedagens automaticamente). Documentação viva em
  `/admin/api-clients-docs` dentro do próprio painel.
- `resources/css/app.css` — Tailwind v4 + uma camada de compatibilidade
  com nomes de classe do Bootstrap (`.btn`, `.card`, `.badge` etc.),
  porque a maioria das ~100 views ainda usa esse vocabulário — só o que
  essas classes RENDERIZAM foi trocado pro visual novo, não as views.
  Cuidado ao adicionar uma classe nova: se ela também bater com um
  padrão de utility do Tailwind (`bg-*`, `mb-*`, `h-100` etc.), ela some
  se cair dentro de `@layer components` sem querer — regra fora de
  `@layer` sempre vence regra dentro de `@layer`, independente de ordem
  no arquivo (foi a causa de pelo menos dois bugs visuais reais já
  corrigidos: cards esticando pra altura inteira da página, overlay do
  gerenciador de arquivos preso visível).
