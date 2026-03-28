<?php

return [
    'title' => 'Notificações do Discord',
    'description' => 'Configure canais do Discord para receber notificações de eventos do site.',

    'add' => 'Adicionar canal',
    'edit' => 'Editar',
    'enable' => 'Ativar',
    'disable' => 'Desativar',
    'test' => 'Testar',
    'test_title' => 'Mensagem de teste',
    'test_description' => '✅ O canal está funcionando!',

    'cols' => [
        'channel' => 'Canal',
        'events' => 'Notificações',
        'stats' => 'Estatísticas',
        'actions' => 'Ações',
    ],

    'empty' => [
        'title' => 'Nenhum canal ainda',
        'sub' => 'Adicione um canal do Discord para começar a receber notificações de eventos do site.',
    ],

    'embed' => [
        'registered_on' => 'registrado em',
        'verified' => 'verificou sua conta',
        'provider' => 'Provedor',
        'amount' => 'Valor',
        'promo' => 'Código promocional',
        'payment_failed_desc' => 'Uma tentativa de pagamento falhou.',
    ],

    'fields' => [
        'channel_id' => 'ID do canal do Discord',
        'url' => 'URL do Webhook',
        'events' => 'Eventos',
        'color' => 'Cor',
        'bot_name' => 'Nome do bot',
        'bot_avatar' => 'Avatar do bot',
    ],

    'placeholders' => [
        'events' => 'Selecione os eventos...',
    ],

    'hints' => [
        'channel_id' => 'Clique com o botão direito no canal no Discord → "Copiar ID do canal". Requer o modo desenvolvedor ativado nas configurações do Discord.',
        'url' => 'Configurações do canal → Integrações → Webhooks → Criar → Copiar URL.',
        'events' => 'Quais eventos enviar para este canal.',
        'color' => 'Faixa lateral das mensagens.',
        'bot_name' => 'Opcional. Como o bot será exibido no Discord.',
        'bot_avatar' => 'Opcional. Imagem quadrada.',
    ],

    'events' => [
        'user_registered' => 'Registro',
        'user_logged_in' => 'Login',
        'user_verified' => 'Verificação',
        'social_linked' => 'Conta social vinculada',
        'payment_success' => 'Pagamento aprovado',
        'payment_failed' => 'Pagamento falhou',
    ],

    'messages' => [
        'saved' => 'Canal salvo.',
        'deleted' => 'Canal excluído.',
        'test_ok' => 'Teste enviado — verifique o Discord!',
        'test_fail' => 'Falha ao enviar. Verifique a URL do webhook.',
    ],

    'errors' => [
        'no_channel_id' => 'Informe o ID do canal do Discord.',
        'invalid_url' => 'Deve ser uma URL de Webhook do Discord.',
        'no_events' => 'Selecione pelo menos um evento.',
    ],

    'confirms' => [
        'delete' => 'Excluir canal? As notificações serão interrompidas.',
    ],
];
