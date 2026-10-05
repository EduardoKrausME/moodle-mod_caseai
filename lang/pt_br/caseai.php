<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * caseai.php
 *
 * @package   mod_caseai
 * @copyright 2026 Eduardo Kraus {@link https://eduardokraus.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['aisummary'] = 'Resumo assistido por IA';
$string['assessment'] = 'Avaliação';
$string['attempt'] = 'Tentativa';
$string['attemptalreadyfinished'] = 'Esta tentativa já foi finalizada.';
$string['attemptstatus'] = 'Status: {$a}';
$string['caseai:addinstance'] = 'Adicionar um novo estudo de caso adaptativo';
$string['caseai:attempt'] = 'Responder estudo de caso adaptativo';
$string['caseai:grade'] = 'Avaliar manualmente tentativas do estudo de caso';
$string['caseai:view'] = 'Visualizar estudo de caso adaptativo';
$string['caseai:viewreports'] = 'Visualizar relatórios do estudo de caso';
$string['caseainame'] = 'Nome da atividade';
$string['caseconfig'] = 'Configuração do caso';
$string['charactersjson'] = 'Personagens (JSON)';
$string['completionattempt'] = 'Exigir tentativa concluída';
$string['completionattempt_desc'] = 'O aluno deve alcançar um critério determinístico de encerramento ou o número máximo de rodadas configurado.';
$string['consequence'] = 'Consequência apresentada';
$string['continueattempt'] = 'Continuar caso';
$string['decision'] = 'Decisão';
$string['endcriteriajson'] = 'Critérios determinísticos de encerramento (JSON)';
$string['error:airesponsetoolong'] = 'A resposta da IA excedeu o tamanho permitido. O estado da tentativa não foi alterado.';
$string['error:attemptfinished'] = 'Esta tentativa já foi finalizada.';
$string['error:attemptlocked'] = 'Esta tentativa está sendo atualizada por outra requisição. Recarregue a página e tente novamente.';
$string['error:decisionlength'] = 'A decisão deve ter entre 1 e 12000 caracteres.';
$string['error:enumvaluesrequired'] = 'A variável enum {$a} deve definir um array values.';
$string['error:grade'] = 'A nota máxima deve ficar entre 0 e 1000.';
$string['error:gradingdisabled'] = 'A avaliação manual está desativada nesta atividade.';
$string['error:initialrequired'] = 'A variável de estado {$a} deve definir um valor initial.';
$string['error:invalidgrade'] = 'A nota está fora do intervalo configurado.';
$string['error:invalidjson'] = 'O campo {$a} contém JSON inválido.';
$string['error:invalidstatevalue'] = 'Valor inválido para a variável de estado: {$a}';
$string['error:invalidvariabledefinition'] = 'Definição inválida para a variável de estado: {$a}';
$string['error:invalidvariablename'] = 'Nome inválido de variável de estado: {$a}';
$string['error:invalidvariabletype'] = 'Tipo não suportado para a variável de estado: {$a}';
$string['error:jsonmustbearray'] = 'O campo JSON {$a} deve resultar em um array ou objeto.';
$string['error:malformedairesponse'] = 'A IA retornou JSON inválido ou incompleto. O estado da tentativa não foi alterado.';
$string['error:maxrounds'] = 'A quantidade máxima de rodadas deve ficar entre 1 e 100.';
$string['error:maxroundsreached'] = 'A quantidade máxima de rodadas configurada foi atingida.';
$string['error:ratelimit'] = 'Muitas requisições de simulação em pouco tempo. Tente novamente após a janela atual expirar.';
$string['error:ratelock'] = 'Não foi possível obter com segurança o lock do rate limit. Tente novamente.';
$string['error:staleattempt'] = 'Esta página está desatualizada ou a decisão já foi enviada. Recarregue a tentativa antes de enviar novamente.';
$string['error:summaryfailed'] = 'Não foi possível gerar o resumo assistido por IA. Os dados existentes da tentativa não foram alterados.';
$string['eventsjson'] = 'Eventos possíveis (JSON)';
$string['factsjson'] = 'Fatos que são verdadeiros no caso (JSON)';
$string['firstdecisionprompt'] = 'O que você decide fazer?';
$string['generatesummary'] = 'Gerar/atualizar resumo';
$string['gradingnote'] = 'A nota nunca é gerada automaticamente. Quando a nota máxima for maior que zero, um professor precisa revisar a tentativa e informar a nota manualmente.';
$string['immutablefactsjson'] = 'Fatos que nunca podem mudar (JSON)';
$string['manualgrade'] = 'Nota revisada por humano';
$string['maximumgrade'] = 'Nota máxima';
$string['maxrounds'] = 'Quantidade máxima de rodadas';
$string['modulename'] = 'Estudo de caso adaptativo';
$string['modulenameplural'] = 'Estudos de caso adaptativos';
$string['nocaseais'] = 'Não há estudos de caso adaptativos neste curso.';
$string['objectives'] = 'Objetivos de aprendizagem';
$string['pluginadministration'] = 'Administração do estudo de caso adaptativo';
$string['pluginname'] = 'Estudo de caso adaptativo';
$string['privacy:metadata:attempts'] = 'Armazena cada tentativa do aluno e seu estado determinístico.';
$string['privacy:metadata:attempts:currentstate'] = 'Estado determinístico atual do caso.';
$string['privacy:metadata:attempts:grade'] = 'Nota revisada manualmente, quando a avaliação está habilitada.';
$string['privacy:metadata:attempts:gradedby'] = 'Usuário que avaliou manualmente a tentativa.';
$string['privacy:metadata:attempts:summary'] = 'Resumo opcional assistido por IA para revisão do professor.';
$string['privacy:metadata:attempts:timecreated'] = 'Momento de criação da tentativa.';
$string['privacy:metadata:attempts:timemodified'] = 'Momento da última alteração da tentativa.';
$string['privacy:metadata:attempts:userid'] = 'Usuário proprietário da tentativa.';
$string['privacy:metadata:bridge'] = 'O conteúdo da simulação é enviado pelo local_ai_bridge ao provider configurado para o tenant.';
$string['privacy:metadata:bridge:decision'] = 'A decisão do aluno é enviada para gerar a próxima rodada do caso.';
$string['privacy:metadata:bridge:state'] = 'O estado determinístico atual e a configuração do cenário definida pelo professor são enviados como contexto.';
$string['privacy:metadata:bridge:userid'] = 'O ID Moodle do usuário é usado pelo bridge para roteamento do tenant, permissões e contabilização de uso.';
$string['privacy:metadata:rate'] = 'Armazena contadores persistentes usados para limitar requisições da simulação.';
$string['privacy:metadata:rate:requestcount'] = 'Quantidade de requisições feitas na janela atual.';
$string['privacy:metadata:rate:userid'] = 'Usuário cujas requisições estão sendo contadas.';
$string['privacy:metadata:rate:windowstart'] = 'Início da janela atual de rate limit.';
$string['privacy:metadata:rounds'] = 'Armazena decisões, consequências narrativas e estados determinísticos de cada rodada.';
$string['privacy:metadata:rounds:decisiontext'] = 'Decisão informada pelo aluno.';
$string['privacy:metadata:rounds:evidence'] = 'Evidências extraídas para revisão humana posterior.';
$string['privacy:metadata:rounds:narrative'] = 'Consequência narrativa gerada pela IA.';
$string['privacy:metadata:rounds:state'] = 'Estado determinístico antes ou depois da rodada.';
$string['privacy:metadata:rounds:timecreated'] = 'Momento em que a rodada foi criada.';
$string['ratelimitcount'] = 'Requisições por janela';
$string['ratelimitcount_desc'] = 'Quantidade máxima de requisições de simulação que um usuário pode enviar por atividade dentro da janela configurada.';
$string['ratelimitwindow'] = 'Janela do rate limit (segundos)';
$string['ratelimitwindow_desc'] = 'Duração da janela persistente de rate limit. O mínimo efetivo é 60 segundos.';
$string['rejectedchanges'] = 'Mudanças de estado rejeitadas do retorno da IA';
$string['report'] = 'Relatório do caminho percorrido';
$string['roundlabel'] = 'Rodada {$a}';
$string['rounds'] = 'Rodadas';
$string['roundxofy'] = 'Rodada {$a->current} de até {$a->max}';
$string['rubric'] = 'Rubrica opcional';
$string['savegrade'] = 'Salvar nota';
$string['scenario'] = 'Cenário inicial';
$string['startattempt'] = 'Iniciar caso';
$string['stateafter'] = 'Estado depois';
$string['statebefore'] = 'Estado antes';
$string['stateconfig'] = 'Estado determinístico';
$string['statesjson'] = 'Estados possíveis (JSON)';
$string['studentrole'] = 'Papel do aluno';
$string['submitdecision'] = 'Enviar decisão';
$string['variablesjson'] = 'Definições das variáveis de estado (JSON)';
$string['variablesjson_help'] = 'Cada variável precisa de type, initial e mutable. Tipos suportados: integer, number, boolean, string e enum. Variáveis numéricas podem definir min/max e enum deve definir values.';
$string['viewreport'] = 'Ver relatório do professor';
$string['yourdecision'] = 'Decisão do aluno';
