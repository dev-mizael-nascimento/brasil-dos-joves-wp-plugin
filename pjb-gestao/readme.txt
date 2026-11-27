=== PJB Gestão Social & Acadêmica ===
Contributors: brasildosjovens
Tags: ong, cursos, certificados, agendamento, assistencia social
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Gestão social, cursos profissionalizantes, turmas, frequência, agendamento de atendimentos e certificados digitais com QR Code.

== Description ==

O **PJB Gestão Social & Acadêmica** transforma o WordPress em uma plataforma completa de gestão para organizações do terceiro setor, projetos sociais e centros de qualificação profissional:

* **Gestão de Beneficiários**: Cadastro completo com triagem socioeconômica, cálculo de renda per capita, NIS, CadÚnico, escolaridade e nível de vulnerabilidade social.
* **Cursos & Turmas**: Cadastro de cursos com carga horária, faixa etária, escolaridade mínima exigida, conteúdo programático e controle de turmas e vagas.
* **Chamada & Frequência**: Diário de classe com cálculo automático de assiduidade, alertas de evasão e impressão de diário oficial em A4.
* **Certificados Digitais com QR Code (Frente e Verso)**: Emissão com assinatura criptográfica HMAC, conteúdo programático automático no verso, validação pública e 2ª via por CPF em modo somente leitura.
* **Agendamento de Atendimentos**: Gestão de profissionais (saúde, jurídico, psicologia, social), duração média configurável, escala semanal ou por datas específicas e agendamento público por etapas.
* **Esporte, Cultura, Doações & Financeiro**: Controle de oficinas, campanhas sociais, estoque de cestas básicas e fluxo de caixa.
* **Comunicação & IA (Opcional)**: Integração opcional com Google Gemini para pareceres sociais/editais e envio de avisos via SMTP ou link do WhatsApp.
* **Shortcodes Públicos**: `[pjb_inscricao]`, `[pjb_agendamento]`, `[pjb_validar]` e `[pjb_painel]`.

== Installation ==

1. Acesse o painel do seu WordPress em **Plugins > Adicionar Novo**.
2. Clique em **Enviar Plugin** e selecione o arquivo `pjb-gestao.zip` (ou instale diretamente pelo diretório oficial do WordPress).
3. Clique em **Instalar Agora** e depois em **Ativar Plugin**.
4. Acesse o novo menu **PJB Gestão** no painel do WordPress.
5. (Opcional) Insira os shortcodes `[pjb_inscricao]`, `[pjb_agendamento]` ou `[pjb_validar]` em suas páginas públicas.

== Frequently Asked Questions ==

= Os certificados possuem validação contra fraudes? =
Sim. Cada certificado emitido recebe um código único, assinatura criptográfica HMAC-SHA256 e QR Code apontando para o portal público de validação (`/validar` ou página com `[pjb_validar]`).

= Os alunos conseguem tirar a 2ª via do certificado sozinhos? =
Sim. Na página de validação pública (`[pjb_validar]`), o aluno pode pesquisar pelo próprio CPF e imprimir frente e verso do certificado emitido, sem permissão de edição.

= A integração com Inteligência Artificial é obrigatória? =
Não. Todos os módulos funcionam 100% sem chave de API. A integração com o Google Gemini é opcional para auxiliar assistentes sociais e gestores na redação de pareceres e propostas.

== External Services ==

Este plugin pode se conectar aos seguintes serviços externos sob demanda ou configuração do administrador:

1. **Google Gemini API (`https://generativelanguage.googleapis.com`)**
   - **Finalidade:** Geração opcional de pareceres sociais técnicos, relatórios de impacto e propostas de editais com IA.
   - **Quando é enviado:** Apenas quando o administrador configura uma chave de API do Google Gemini em *Configurações* e aciona um botão de geração de texto com IA.
   - **Termos e Privacidade:** [Google AI Terms](https://ai.google.dev/gemini-api/terms) | [Google Privacy Policy](https://policies.google.com/privacy)

2. **QRServer API (`https://api.qrserver.com`)**
   - **Finalidade:** Geração de imagem QR Code vinculada à URL pública de validação do certificado emitido.
   - **Quando é enviado:** Ao emitir ou exibir o registro de certificado contendo o link público de validação.
   - **Política:** [QRServer Info](https://goqr.me/api/)

3. **WhatsApp Click-to-Chat (`https://api.whatsapp.com`)**
   - **Finalidade:** Abertura de link direto no WhatsApp do usuário/equipe com mensagem pré-preenchida de confirmação de matrícula, agendamento ou certificado.
   - **Quando é enviado:** Apenas mediante clique explícito no botão de envio pelo usuário.
   - **Termos:** [WhatsApp Legal](https://www.whatsapp.com/legal/)

== Source Code ==

A interface administrativa e pública deste plugin é compilada com React 19, TypeScript, Vite e Tailwind CSS v4. Todo o código-fonte não minificado (`src/`) e as instruções de build (`package.json`, `vite.config.ts`) estão disponíveis publicamente no repositório oficial:

* Repositório: https://github.com/dev-mizael-nascimento/PJB-brasil-dos-joves-
* Comando para recompilar os assets em `build/`: `npm install && npm run build`

== Screenshots ==

1. Painel Executivo com indicadores, gráficos e acompanhamento de turmas.
2. Módulo de Cursos & Turmas com matriz curricular e diário de chamada.
3. Certificado oficial Frente e Verso (A4 Paisagem) com conteúdo programático e QR Code.
4. Portal público de inscrição e agendamento em etapas com busca por CPF.

== Changelog ==

= 1.2.0 =
* Adequação completa às diretrizes oficiais do diretório de plugins do WordPress.org.
* Adicionado campo de Escolaridade Mínima no cadastro de cursos e Escolaridade Atual na matrícula pública.
* Adicionado endpoint nativo de autenticação WordPress (`/auth/login`) e melhorias na busca por CPF.
* Melhorias no contraste dos modos Claro e Escuro e isolamento de impressão para certificados e comprovantes.

= 1.0.0 =
* Lançamento inicial do plugin PJB Gestão Social & Acadêmica.

== Upgrade Notice ==

= 1.2.0 =
Atualização estrutural com adequações ao WordPress.org, suporte a escolaridade nos cursos e melhorias visuais.
