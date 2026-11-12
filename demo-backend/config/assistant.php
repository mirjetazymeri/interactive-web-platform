<?php

/*
|--------------------------------------------------------------------------
| Smart Assistant
|--------------------------------------------------------------------------
| Deterministic matching settings. Content (questions, answers, synonyms)
| is managed in Admin → AI Assistant; this file only tunes the engine.
| Word lists are written without diacritics because the normalizer folds
| them (ë → e, ç → c) before matching.
*/

return [
    // Shared secret (at least 32 characters) a frontend sends as X-Assistant-Preview to try the assistant
    // while it is hidden from visitors. Leave empty in production; preview traffic is never logged.
    'preview_token' => env('ASSISTANT_PREVIEW_TOKEN', ''),

    // Future, optional AI integration (inactive). Providers are names an admin can pre-select; nothing calls
    // them. An adapter implementing App\Services\Assistant\Ai\GenerativeAdapter must be registered here, with
    // its safeguards and tests, before "hybrid" or "generative" can even be chosen. None exists yet.
    'ai' => [
        'providers' => [
            'openai' => 'OpenAI',
            'anthropic' => 'Anthropic Claude',
            'google' => 'Google Gemini',
            'custom' => 'Custom provider',
        ],
        'adapters' => [
            // 'anthropic' => App\Services\Assistant\Ai\Adapters\AnthropicAdapter::class,
        ],
    ],

    // Longest visitor message accepted, in characters.
    'max_length' => 300,

    // Requests per minute per client IP.
    'rate_limits' => [
        'message' => 20,
        'bootstrap' => 60,
    ],

    // Compiled index lifetime; content changes are picked up immediately through the cache fingerprint.
    'cache_ttl_minutes' => 1440,

    'matching' => [
        // Score at or above which an answer is given without asking.
        'confident' => 0.5,
        // Lowest score still offered as a "did you mean" choice.
        'candidate' => 0.25,
        // A confident answer must lead the runner-up by at least this much.
        'margin' => 0.1,
        // Share of the visitor's words a "did you mean" choice must account for.
        'min_coverage' => 0.5,
        // Maximum choices offered when a question is ambiguous.
        'max_candidates' => 3,
        // Score credited to a typo-tolerant word match instead of an exact one.
        'fuzzy_weight' => 0.8,
        // Words shorter than this are never matched fuzzily.
        'fuzzy_min_length' => 5,
    ],

    // Short replies that ask for the next suggested follow-up of the previous answer.
    'continue_phrases' => [
        'en' => ['yes', 'yeah', 'yep', 'sure', 'ok', 'okay', 'more', 'tell me more', 'more please', 'more details', 'go on', 'continue', 'tell me more about it', 'more about it', 'more about that', 'and then', 'what else'],
        'sq' => ['po', 'ok', 'mire', 'me shume', 'me teper', 'vazhdo', 'dua me shume', 'me shume detaje', 'na trego me shume', 'me trego me shume', 'me shume per kete', 'cfare tjeter'],
    ],

    // Detecting the visitor's language from the question itself, so an Albanian question on the English site is
    // answered in Albanian. Words that only one language uses count 1 each (written folded: ë → e, ç → c); words
    // that only one language's admin content uses count half. Below the minimum, or without a clear lead, the
    // conversation's previous language or the site's language is kept.
    'language' => [
        'min_score' => 2,
        // The winner needs at least this multiple of the runner-up's score.
        'lead' => 1.5,
        'markers' => [
            'en' => [
                'what', 'whats', 'how', 'do', 'does', 'did', 'you', 'your', 'yours', 'can', 'could', 'would', 'should', 'will', 'is', 'are',
                'was', 'were', 'the', 'have', 'has', 'about', 'with', 'for', 'show', 'tell', 'give', 'build', 'make', 'create', 'much', 'long',
                'cost', 'price', 'which', 'who', 'where', 'when', 'why', 'want', 'need', 'help', 'please', 'there', 'this', 'that', 'it', 'my',
                'our', 'we', 'they', 'offer', 'provide', 'any', 'some', 'of', 'and', 'or', 'to', 'an', 'in', 'on', 'from', 'like', 'something',
                'start', 'book', 'develop', 'design', 'examples', 'services', 'company', 'businesses', 'systems', 'yes', 'also', 'other',
            ],
            'sq' => [
                'cfare', 'cka', 'si', 'sa', 'ku', 'kur', 'pse', 'kush', 'cili', 'cila', 'cilat', 'cilet', 'beni', 'boni', 'bon', 'bejme', 'ofroni',
                'ofron', 'keni', 'kemi', 'kam', 'jeni', 'jemi', 'eshte', 'jane', 'mund', 'te', 'per', 'nje', 'dhe', 'ose', 'edhe', 'juaj', 'tuaj',
                'tona', 'jone', 'ju', 'na', 'ne', 'une', 'kjo', 'ky', 'ajo', 'kete', 'keto', 'ate', 'cmim', 'cmimi', 'kushton', 'zgjat', 'dua',
                'duam', 'nevoje', 'ndihme', 'ndihmoni', 'pershendetje', 'faleminderit', 'mirupafshim', 'shume', 'projekte', 'projektet',
                'projekti', 'sherbime', 'sherbimet', 'faqe', 'webfaqe', 'uebfaqe', 'aplikacion', 'aplikacione', 'dyqan', 'biznes', 'biznese',
                'biznesin', 'sisteme', 'kompani', 'trego', 'tregoni', 'ndertoni', 'krijoni', 'zhvilloni', 'punoni', 'takim', 'kontaktoj',
                'fillojme', 'ngjashme', 'tille', 'dicka', 'vetem', 'nga', 'apo', 'nese', 'qe', 'se', 'tim', 'time', 'tonin',
            ],
        ],
        // "A beni…?": a leading question particle that only Albanian uses this way.
        'leading' => ['sq' => ['a']],
        // Words that on their own show the language (greetings, thanks and their chat abbreviations): "prsh", "flm".
        'strong' => ['sq' => ['prsh', 'prshh', 'persh', 'pershendetje', 'tung', 'tungjatjeta', 'klm', 'ckemi', 'flm', 'faleminderit', 'rrofsh',
            'mirmengjes', 'mirdita', 'mirembrema', 'mirmbrema', 'mirupafshim', 'pafshim']],
    ],

    // What a question asks for, independent of its subject (written folded; whole words, in order). The engine
    // uses these to let an explicit request win over a name: "How much would a Treforma-like shop cost?" is a
    // pricing question, "Can you build a website like Treforma?" a similar-project request, not "about Treforma".
    // Stronger requests first; the first that matches is the request type.
    'acts' => [
        'en' => [
            'pricing' => ['how much', 'cost', 'costs', 'price', 'prices', 'pricing', 'budget', 'quote', 'quotes', 'quotation', 'estimate', 'estimates', 'charge', 'fee', 'fees', 'rates'],
            'timeline' => ['how long', 'timeline', 'timeframe', 'time frame', 'duration', 'deadline', 'how fast', 'how quickly'],
            'contact' => ['start a project', 'get started', 'how can we start', 'how do we start', 'can we start', 'contact', 'reach you',
                'talk to', 'speak to', 'book', 'meeting', 'call me', 'hire you', 'work with you', 'work together'],
            'similar' => ['similar', 'comparable', 'same as', 'such as', 'something like', 'like that', 'like this', 'like it', 'like yours', 'like the'],
            'projects' => ['project', 'projects', 'portfolio', 'example', 'examples', 'case study', 'case studies', 'your work'],
            'capability' => ['do you', 'can you', 'could you', 'are you able', 'will you', 'is it possible', 'i need', 'we need', 'i want',
                'we want', 'looking for', 'help with', 'help me', 'help us', 'offer', 'provide', 'build', 'make', 'create', 'develop', 'design', 'set up'],
            'information' => ['what is', 'what are', 'tell me about', 'who is', 'explain', 'what does', 'more about'],
            // A follow-up that only names a new subject: "what about mobile?", "and SEO?".
            'ellipsis' => ['what about', 'how about', 'and', 'and for', 'also'],
        ],
        'sq' => [
            'pricing' => ['sa kushton', 'kushton', 'kushtojne', 'kushtoj', 'kushtoje', 'kushtu', 'oferten', 'ofertes', 'sa bon', 'sa ban', 'cmim', 'cmimi', 'cmimet', 'cmimin', 'kosto', 'kostoja', 'buxhet', 'buxheti', 'oferte', 'sa del', 'sa pagoj'],
            'timeline' => ['sa zgjat', 'zgjat', 'sa kohe', 'afat', 'afati', 'kohezgjatje', 'kohezgjatja', 'sa shpejt'],
            'contact' => ['te fillojme', 'fillojme', 'ta fillojme', 'nisim', 'kontakt', 'kontaktoj', 'kontaktoni', 'takim', 'rezervoj', 'te flas', 'flas me', 'bashkepunojme', 'bashkepunim'],
            'similar' => ['e ngjashme', 'te ngjashme', 'i ngjashem', 'te ngjashem', 'ngjashem', 'njesoj si', 'sikur', 'si ajo', 'si kjo', 'si ky', 'si ai', 'si ate', 'si kete', 'dicka te tille', 'dicka si'],
            'projects' => ['projekt', 'projekte', 'projektet', 'projekti', 'projektin', 'portofol', 'portofoli', 'shembull', 'shembuj', 'punet tuaja'],
            'capability' => ['a beni', 'a boni', 'a ofroni', 'a ndertoni', 'a krijoni', 'a zhvilloni', 'a dizajnoni', 'a mund', 'mund te', 'a keni',
                'a merreni', 'a punoni', 'kam nevoje', 'kemi nevoje', 'me duhet', 'na duhet', 'dua', 'duam', 'ndihme', 'ndihmoni',
                'beni', 'boni', 'ofroni', 'ndertoni', 'krijoni', 'zhvilloni'],
            'information' => ['cfare eshte', 'cka eshte', 'cfare jane', 'kush eshte', 'me trego per', 'me tregoni per', 'na trego per', 'na tregoni per', 'me shume per', 'shpjego', 'cfare ben'],
            'ellipsis' => ['po per', 'dhe per', 'edhe per', 'cfare per', 'po te'],
        ],
        // "like" after these is a wish, not a comparison: "I would like a website".
        'not_similar_after' => ['would', 'i', 'we', 'you', 'they', 'd', 'also'],
        // "something like that", "si kjo": the request refers to what was just discussed.
        'reference' => [
            'en' => ['that', 'this', 'it', 'one', 'something', 'same', 'them', 'those'],
            'sq' => ['ajo', 'kjo', 'ate', 'kete', 'dicka', 'njejte', 'ky', 'ai', 'tille', 'ta', 'te'],
        ],
        // "like X", "si X": the word right before a named project makes it a comparison.
        'comparison' => ['en' => ['like', 'similar'], 'sq' => ['si', 'sikur']],
    ],

    // How a question is split into clauses (written folded, whole words, longest first). A negator starts a
    // negative clause ("not marketing", "instead of 3D products"); an affirmation starts a positive one ("I need",
    // "dua"); "and"/"with" join requirements ("a website and hosting"); "or" offers alternatives. Punctuation
    // always ends a clause. Services named only in negative clauses are excluded, never recommended.
    'clauses' => [
        'en' => [
            'negators' => ['instead of', 'rather than', 'not', 'no', 'never', 'without', 'except', 'nor'],
            'affirm' => ['i need', 'we need', 'i want', 'we want', 'i mean', 'i would like', 'we would like', 'i prefer', 'but', 'actually', 'only'],
            'and' => ['and', 'plus', 'with', 'also', 'as well as', 'together with'],
            'or' => ['or', 'either'],
            // The visitor changes their mind: an earlier topic gives way.
            'correction' => ['actually', 'instead', 'i mean', 'rather', 'not', 'instead of'],
        ],
        'sq' => [
            'negators' => ['ne vend te', 'nuk', 'jo', 's', 'mos', 'pa', 'asnje'],
            'affirm' => ['dua', 'du', 'duam', 'kam nevoje', 'kemi nevoje', 'me duhet', 'na duhet', 'e kam fjalen', 'por', 'po', 'vec', 'ne fakt'],
            'and' => ['dhe', 'edhe', 'plus', 'si dhe', 'bashke me', 'me', 'qe lidhet me'],
            'or' => ['ose', 'apo'],
            'correction' => ['e kam fjalen', 'ne fakt', 'jo', 'ne vend te', 'vec', 'nuk'],
        ],
    ],

    // Follow-ups that refine the request just discussed ("something simple", "for clothes instead") rather than
    // naming a new service.
    'refine' => [
        'en' => ['simple', 'simpler', 'basic', 'small', 'smaller', 'minimal', 'complex', 'advanced', 'bigger', 'larger'],
        'sq' => ['thjeshte', 'thjesht', 'dicka', 'bazik', 'bazike', 'vogel', 'kompleks', 'komplekse', 'madhe', 'avancuar', 'shume'],
    ],

    // Words that make "mobile" mean the website itself working on phones, not a separate app.
    'responsive' => [
        'en' => ['work on mobile', 'work on phones', 'work on a phone', 'on mobile', 'on phones', 'on my phone', 'mobile friendly', 'responsive', 'on smartphones', 'on tablets'],
        'sq' => ['ne celular', 'ne telefon', 'punon ne', 'responsive', 'per celular'],
    ],
    // Website-like topics ("website", "online store"): a "mobile" follow-up about them means a responsive site.
    'web_words' => ['web', 'website', 'websit', 'webfaq', 'ecommerce', 'store', 'shop', 'webshop', 'faqe', 'dyqan', 'platform'],

    // Words that make "mobile" a separate application rather than the website on phones.
    'app_words' => ['app', 'apps', 'application', 'applications', 'aplikacion', 'aplikacioni', 'aplikacione', 'aplikacionin', 'ios', 'android', 'native'],

    // Words that connect requirements to each other ("an app that connects to our ERP"): every service the
    // question names then counts, with the integration itself.
    'integration' => [
        'en' => ['connect', 'connects', 'connected', 'connecting', 'integrate', 'integrated', 'integrates', 'integration', 'sync', 'synced', 'link', 'linked', 'talks to'],
        'sq' => ['lidhet', 'lidh', 'lidhje', 'lidhni', 'integrim', 'integrohet', 'integroni', 'sinkronizim', 'nderlidhje'],
    ],

    // Words that only address the assistant: "hello chat" is a greeting, not a question about chat.
    'addressees' => ['chat', 'bot', 'chatbot', 'assistant', 'asistent', 'asistenti', 'prog', 'there', 'team', 'ekip', 'ekipi'],

    // Typo recovery against the domain vocabulary (service names, admin phrases, synonyms, question words):
    // "webiste" → "website". Only words of at least min_length letters, starting with the same letter, with one
    // unambiguous closest word; two edits are allowed from long_length letters.
    'typos' => ['min_length' => 5, 'long_length' => 8],

    // Words that may surround a service or project name in a question ("do you offer X", "show the X project").
    // A question naming a service or project is answered from the CMS only when its other words come from here.
    'entity_words' => [
        'en' => [
            'service' => ['service', 'solution', 'offer', 'build', 'develop', 'create', 'make', 'help', 'support', 'company', 'work', 'business', 'website', 'app', 'more', 'information', 'details', 'related', 'about',
                'integrate', 'connect', 'set', 'setup', 'install', 'implement', 'need', 'want', 'looking', 'someone', 'handle', 'something', 'similar', 'comparable', 'kind', 'type', 'manage', 'maintain', 'run', 'request'],
            'project' => ['project', 'work', 'example', 'portfolio', 'case', 'study', 'client', 'built', 'made', 'more', 'information', 'details', 'related', 'about', 'recent', 'latest'],
        ],
        'sq' => [
            'service' => ['sherbim', 'zgjidhje', 'ofroni', 'ofron', 'ndertoni', 'ndertim', 'krijoni', 'zhvillim', 'zhvilloni', 'beni', 'boni', 'bon', 'ndihme', 'mbeshtetje', 'kompani', 'biznes', 'faqe', 'aplikacion', 'informacion', 'detaje',
                'nevoje', 'ndihmoni', 'punoni', 'merreni', 'dizajnoni', 'implementoni', 'lidhni', 'instaloni', 'dicka', 'ngjashme', 'ngjashem', 'tille', 'lloj'],
            'project' => ['projekt', 'pune', 'shembull', 'portofol', 'klient', 'informacion', 'detaje', 'lidhur', 'fundit'],
        ],
    ],

    'stopwords' => [
        'en' => [
            'a', 'about', 'also', 'am', 'an', 'and', 'any', 'anything', 'are', 'as', 'at', 'be', 'been', 'but', 'by', 'can', 'could',
            'did', 'do', 'does', 'for', 'from', 'get', 'give', 'had', 'has', 'have', 'hello', 'hey', 'hi', 'how', 'i', 'if', 'in',
            'into', 'is', 'it', 'its', 'just', 'know', 'let', 'like', 'me', 'much', 'my', 'need', 'of', 'on', 'or', 'our',
            'please', 'provide', 'see', 'show', 'so', 'some', 'tell', 'that', 'the', 'their', 'them', 'there', 'these', 'they', 'this', 'those',
            'to', 'us', 'want', 'was', 'we', 'were', 'what', 'when', 'where', 'which', 'who', 'why', 'will', 'with', 'would',
            'you', 'your', 'yours',
            // Quantities are read before filtering (see quantities) and carry no topic.
            'one', 'two', 'three', 'four', 'five', 'all', 'every', 'single', 'related', 'regarding',
            // Negation and correction words: read by the clause analysis, never a topic.
            'not', 'no', 'never', 'instead', 'actually', 'rather', 'mean', 'but', 'only', 'either', 'nor', 'without', 'except',
        ],
        // "ai" (he) is deliberately missing: it is also "AI", which names services.
        'sq' => [
            'a', 'ajo', 'apo', 'as', 'ata', 'ate', 'atij', 'cfare', 'deri', 'do', 'dhe', 'e', 'edhe', 'eshte', 'i', 'jam',
            'jane', 'ju', 'juaj', 'jush', 'ka', 'kam', 'keni', 'kemi', 'kete', 'keto', 'kjo', 'ky', 'me', 'mua', 'mund', 'na',
            'ne', 'nese', 'nga', 'ni', 'nje', 'ose', 'pak', 'per', 'pershendetje', 'pra', 'pse', 'qe', 'sa', 'se', 'si', 'te',
            'tek', 'ti', 'tona', 'tuaj', 'tung', 'u', 'une', 'vetem', 'jeni', 'mi', 'kur', 'ku', 'cili', 'cila',
            'cilat', 'cilet', 'dua', 'duhet', 'tregoni', 'trego', 'ndonje',
            'dy', 'tre', 'kater', 'pese', 'gjitha', 'lidhur',
            'nuk', 'jo', 'mos', 'pa', 'vec', 'po', 'fjalen', 'fakt',
            // Kosovo colloquial forms: "a muj me marr ofertë", "qysh", "ni", "du".
            'muj', 'mundem', 'mundet', 'mujm', 'qysh', 'ni', 'du', 'marr', 'perafersisht', 'veq',
            // Greeting and thanks abbreviations: never a topic, so "prsh, a beni webfaqe?" is read as the question.
            'prsh', 'prshh', 'persh', 'klm', 'flm', 'ckemi',
        ],
    ],

    // Quantity words: "show me two projects", "me trego nje projekt". Digits 1–9 are read too.
    'quantities' => [
        'en' => ['one' => 1, 'single' => 1, 'two' => 2, 'three' => 3, 'four' => 4, 'five' => 5, 'all' => 'all', 'every' => 'all'],
        'sq' => ['nje' => 1, 'dy' => 2, 'tre' => 3, 'kater' => 4, 'pese' => 5, 'gjitha' => 'all'],
    ],

    // Cards an answer shows before "view all": a conversation, not a catalogue.
    'cards' => ['default' => 3, 'max' => 6],

    // Light suffix stripping so "projects"/"project" and "projektet"/"projekti" meet. Longest suffix first.
    'suffixes' => [
        'sq' => ['ave', 'eve', 'ret', 've', 'at', 'et', 'it', 'in', 'un', 'en', 'ne', 'a', 'i', 'u', 'e'],
    ],

    // Minimum stem length left after stripping a suffix.
    'min_stem' => 4,
];
