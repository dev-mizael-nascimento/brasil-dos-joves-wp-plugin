# Brasil dos Jovens (PJB Gestão)

Plugin customizado para WordPress desenvolvido para o projeto 'Brasil dos Jovens', oferecendo recursos avançados de administração, criptografia, integração com inteligência artificial, configurações de SMTP e gerenciamento completo de dados e permissões.

![PHP](https://img.shields.io/badge/PHP-777BB4?logo=php&logoColor=black&style=for-the-badge) ![HTML](https://img.shields.io/badge/HTML5-E34F26?logo=html5&logoColor=white&style=for-the-badge) ![WordPress Plugin](https://img.shields.io/badge/WordPress%20Plugin-27272A?style=for-the-badge) ![Git](https://img.shields.io/badge/Git-F05032?logo=git&logoColor=white&style=for-the-badge)

- **Painel Administrativo Completo**: Views customizadas para gerenciar as configurações do plugin no painel do WordPress.
- **Integração com IA**: Serviço dedicado (`class-pjb-ai-service.php`) para processamento e interação com inteligência artificial.
- **Segurança e Criptografia**: Módulo dedicado (`class-pjb-crypto.php`) para tratamento seguro de dados sensíveis.
- **Gerenciamento de Banco de Dados**: Estrutura própria para manipulação e persistência de dados no WordPress.
- **Controle de Papéis e Permissões**: Gerenciamento refinado de níveis de acesso e capacidades de usuários (`class-pjb-roles.php`).
- **Configuração de SMTP**: Envio seguro de e-mails configurado diretamente pelo plugin.
- **API REST**: Endpoints customizados para comunicação externa e integração (`class-pjb-rest-api.php`).
- **Shortcodes Públicos**: Elementos prontos para exibição de conteúdos no front-end do site.
- **Sistema de Atualização Automática**: Módulo de verificação e atualização integrado via `update-info.json` e `class-pjb-updater.php`.

## 🛠️ Tecnologias

<div align="left">
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/php/php-original.svg" height="40" alt="php logo" />
  <img width="12" />
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/html5/html5-original.svg" height="40" alt="html5 logo" />
  <img width="12" />
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/git/git-original.svg" height="40" alt="git logo" />
</div>

Abaixo está a organização dos principais diretórios e arquivos do repositório:

```text
npjb-gestao/
├── admin/
│   ├── class-pjb-admin.php
│   └── views/
│       ├── admin-display.php
│       └── settings-display.php
├── includes/
│   ├── class-pjb-activator.php
│   ├── class-pjb-ai-service.php
│   ├── class-pjb-crypto.php
│   ├── class-pjb-database.php
│   ├── class-pjb-deactivator.php
│   ├── class-pjb-rest-api.php
│   ├── class-pjb-roles.php
│   ├── class-pjb-smtp.php
│   └── class-pjb-updater.php
├── public/
│   └── class-pjb-shortcodes.php
├── pjb-gestao.php
├── readme.txt
├── uninstall.php
└── update-info.json
```

Siga os passos abaixo para instalar e configurar o plugin em seu ambiente WordPress:

### Pré-requisitos

- **WordPress** instalado (versão compatível com plugins atuais em PHP)
- **PHP** 7.4 ou superior

### Instalação

1. Clone o repositório ou baixe o código fonte em formato compactado (`pjb-gestao.zip`):
   ```bash
   git clone https://github.com/dev-mizael-nascimento/brasil-dos-joves-wp-plugin.git
   ```
2. Envie a pasta `pjb-gestao` para o diretório de plugins do seu WordPress (`/wp-content/plugins/`), ou compacte a pasta e faça o upload através do painel administrativo do WordPress em **Plugins > Adicionar novo**.
3. Ative o plugin **Brasil dos Jovens (PJB Gestão)** através do menu **Plugins** no painel do WordPress.
4. Acesse as configurações recém-criadas no menu administrativo para configurar os parâmetros de SMTP, chaves de IA e demais opções.
