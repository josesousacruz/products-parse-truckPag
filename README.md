# 🍽️ Open Food Facts API - Laravel Challenge TruckPag

API para importação, armazenamento e gerenciamento de dados alimentícios com base na base de dados pública [Open Food Facts](https://br.openfoodfacts.org/data).
---
This is a challenge by Coodesh


## 📚 Sobre o Projeto

Este projeto foi desenvolvido como parte de um desafio técnico proposto pela **TruckPag**. Ele consiste em consumir dados do Open Food Facts, armazená-los em uma base MongoDB e expô-los via uma API Laravel.

---
## 📄 Documentação da API

A documentação da API está disponível via Swagger UI:

🔗 **[Clique aqui para acessar a documentação](http://localhost:8000/api/documentation)**
**A aplicação precisa estar rodando para acessar a documentação**

## 🚀 Tecnologias Utilizadas

### 🧱 Back-End

- **Linguagem**: PHP 8.2  
- **Framework**: Laravel 10  
- **Banco de Dados**: MongoDB (MongoDB Atlas)  
- **Gerenciador de Dependências**: Composer  

### 📦 Bibliotecas e Pacotes

- [`mongodb/mongodb`](https://github.com/mongodb/mongo-php-library) — Driver MongoDB para PHP  
- Laravel HTTP Client (baseado em Guzzle) — para requisições aos arquivos `.json.gz`  


### 🛠️ Ferramentas

- **Docker Compose** — para containerização do ambiente e orquestração de serviços  
- **Supervisor** — gerenciamento de múltiplos processos simultâneos (API, queue worker, scheduler)  
- **Postman** — testes manuais da API  
- **Git** — versionamento do projeto

## 🚀 Como Instalar e Usar o Projeto

Você pode rodar este projeto **com ou sem Docker**. Abaixo estão as instruções para ambos os cenários:

---

### ✅ Rodando **sem Docker**

```bash
# Clone o repositório e entre na pasta
git clone https://github.com/josesousacruz/products-parse-truckPag.git
cd food-api-truckpag

# Instale as dependências
composer install

# Configure o MongoDB no .env

# Em terminais separados, execute:

# Terminal 1 - para rodar o projeto
php artisan serve

# Terminal 2 - para processar a fila
php artisan queue:work

# Terminal 3 - para executar os agendamentos a cada minuto
php artisan schedule:work
```
---

### 🐳 Rodando **com Docker**

```bash
# Acesse a pasta docker/
cd docker

# Suba os containers
docker compose up --build -d
```

Esse comando irá:
- Subir o Laravel com PHP
- Iniciar o servidor embutido
- Executar o scheduler e o queue worker via Supervisor

- 
> ⏰ O comando agendado para importar os arquivos do Open Food Facts roda automaticamente todos os dias às **02:00 da manhã**.
> Ou Execute:
```bash
docker exec -it nome-do-container php artisan import:openfoodfacts
```
## 🧪 Como Executar os Testes

```bash
# Sem Docker
php artisan test

# Com Docker
docker exec -it nome-do-container php artisan test

```

## 🛠️ Processo de Desenvolvimento

### Decisão pelo Banco de Dados  
Considerei utilizar MySQL, pois tenho mais experiência e utilizo no dia a dia. No entanto, optei por MongoDB, conforme a proposta do desafio, para explorar uma abordagem NoSQL e facilitar o armazenamento dos dados flexíveis do Open Food Facts.

### Conexão com o Banco de Dados  
O primeiro passo foi garantir a conexão com o banco de dados MongoDB (via Atlas).  
Com a conexão testada e validada, inseri manualmente alguns produtos na collection `products` para implementar rotas, controllers, models e migrations:

## 🧩 Lógica de Importação de Produtos — OpenFoodFacts

### Problemas Iniciais

Inicialmente, a lógica de importação foi implementada em um único job que:
- Baixava o arquivo `.json.gz` completo;
- Descompactava para `.json`;
- Processava o conteúdo linha a linha.

Porém, os arquivos do OpenFoodFacts são **extremamente grandes**, e esse processo causava **estouro de memória** e **timeout** nos containers Docker com recursos limitados.

---

### ✅ Solução Aplicada

Para resolver os problemas de performance e consumo de memória, a lógica foi completamente refatorada com foco em **streaming sob demanda** e **processamento eficiente**:

#### 1. **Streaming direto do `.json.gz`**
- Utilizamos a biblioteca `GuzzleHttp` com a opção `['stream' => true]` para baixar os dados aos poucos.
- Os dados são descompactados em tempo real usando `inflate_init(ZLIB_ENCODING_GZIP)` + `inflate_add()`, evitando salvar ou carregar o arquivo inteiro.
- Cada linha (em formato NDJSON) é processada **conforme chega** via stream, utilizando `strpos()` para detectar quebras de linha.

#### 2. **Buffer e inserção em chunks**
- A cada 100 produtos, os dados são inseridos em lote no banco via `Product::insert($buffer)`.
- Isso reduz o número de queries e o uso de memória.

#### 3. **Ajustes para ambiente restrito (Docker)**
- `set_time_limit(0)` para evitar timeouts em execuções longas.
- `ini_set('memory_limit', '2048M')` para garantir mais espaço (embora o uso real de memória tenha sido drasticamente reduzido).
- Uso de logs (`Log::info`, `Log::warning`, `Log::error`) para monitoramento completo da importação.

---

### 🧠 Arquitetura

- A lógica principal foi extraída para um **Service** (`OpenFoodFactsImportService`) reutilizável em qualquer lugar da aplicação.
- Esse service pode ser usado:
  - Por um **Job assíncrono** (`ImportOpenFoodFactsJob`);
  - Por um **Controller** via rota HTTP (útil para testes manuais ou chamadas diretas).

---

### 📦 Fila de Processamento

- A importação é feita por meio de **fila Laravel (`ShouldQueue`)**, o que mantém a aplicação responsiva.
- Os jobs são gerenciados por **supervisord** no ambiente Docker, garantindo execução contínua.
- O Service pode opcionalmente limitar a importação a um número de registros (`$maxItems`), facilitando controle e testes.

---

### Agendamento de Tarefas  
A rotina de importação é executada diariamente às **2h da manhã** via Laravel Scheduler (`schedule:work`).  

- Em ambiente Docker, o `supervisord` é responsável por manter o processo ativo e verificando a cada minuto.

### Considerações sobre o Docker  
Implementei uma configuração Docker completa com:

- `Dockerfile`  
- `docker-compose.yml`  
- `supervisord.conf`

Tudo pronto para subir rapidamente o ambiente de desenvolvimento.

---

## ⚠️ Pontos Não Implementados

Alguns requisitos do desafio foram considerados mas **não implementados integralmente** por motivo de tempo ou escopo:

- ❌ **Endpoint de busca com ElasticSearch (ou similar)**  
  > A estrutura inicial para uma busca customizada foi iniciada, mas a integração com ElasticSearch não foi concluída.

- ❌ **Sistema de alerta em caso de falhas no Sync dos produtos**  
  > Não implementado. A aplicação atualmente realiza o processo de importação com filas e logs, mas sem alertas automatizados.

- ❌ **Esquema de segurança com API Key nos endpoints**  
  > Planejado, porém não implementado por falta de tempo hábil. A API está atualmente aberta para facilitar os testes.

---

### ✅ Resultado Final

- 💡 Importação **100% em stream**: sem salvar arquivos, sem estourar memória.
- ⚡️ Começa a processar os dados imediatamente enquanto o arquivo ainda está baixando.
- 🐳 Compatível com containers Docker com recursos limitados.
- ♻️ Arquitetura desacoplada, reutilizável e preparada para escalar.

---
