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

- **Docker** — para containerização do ambiente  
- **Docker Compose** — orquestração de serviços  
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


### Lógica de Importação  
Inicialmente implementei a importação em um único job, mas logo percebi que os arquivos `.json.gz` eram extremamente grandes, o que fazia a aplicação ultrapassar os limites de **tempo de execução** e **memória**.


Para contornar isso, refatorei a lógica para dividir a tarefa em **múltiplos jobs pequenos** (chunks), reduzindo drasticamente o risco de falhas por timeout ou estouro de memória.

Também utilizei técnicas específicas para minimizar o uso de memória:

- `stream_copy_to_stream()` foi usado para descompactar os arquivos `.gz` diretamente em `.json`, evitando carregar todo o conteúdo em memória.
- A leitura foi feita linha a linha com `fopen`, `fgets`, `feof` e `fclose`, processando o arquivo em fluxo (stream) em vez de armazená-lo inteiro em arrays.

Essas práticas tornaram o processo mais eficiente e compatível com ambientes restritos como containers Docker.

Além disso, realizei ajustes nos comandos:
- `set_time_limit(0)`
- `ini_set('memory_limit', '2048M')`

### Fila de Processamento  
Utilizei o sistema de **queue do Laravel** com driver `database`, para manter rastreamento e persistência dos jobs.  
No Docker, configurei o `supervisord` para manter os workers ativos em background, garantindo execução contínua.

- Os dados são processados em **chunks de 100 linhas**, cada um gerando um job separado (`ImportOpenFoodFactsChunkJob`).
- Isso aumentou a escalabilidade e evitou travamentos do container, além de permitir reprocessamento seletivo.

### Agendamento de Tarefas  
A rotina de importação é executada diariamente às **2h da manhã** via Laravel Scheduler (`schedule:work`).  

- Em ambiente Docker, o `supervisord` é responsável por manter o processo ativo e rodando a cada minuto.

### Considerações sobre o Docker  
Implementei uma configuração Docker completa com:

- `Dockerfile`  
- `docker-compose.yml`  
- `supervisord.conf`

Tudo pronto para subir rapidamente o ambiente de desenvolvimento.

> ⚠️ **Observação:** Durante os testes, identifiquei que o ambiente Docker possui **limitações de recursos** que podem causar falhas nos jobs de importação (ex: timeout, consumo de memória).  
> Esse erro **não ocorre** ao rodar o projeto diretamente fora do container.  
> Não tive tempo hábil para resolver essa limitação no ambiente Docker.

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


