<?php
declare(strict_types=1);

/**
 * Story data for "Grunt": items, endings, and seven story pages.
 * Every page has exactly four choices.
 *
 * CONDITIONS (used by text segments, choice requirements, outcomes, on_enter):
 *   has / has_any / not_has  item ids in the inventory
 *   flag / not_flag          event flags set during this run
 *   min / max                stat thresholds, e.g. ['insight' => 75]
 *   seen                     true if the player already acted on this page
 *
 * PAGE FIELDS
 *   segments  paragraphs of story text; every segment whose 'if' matches is shown
 *   on_enter  rules applied when the player arrives on the page
 *   choices   four choices
 *
 * CHOICE FIELDS
 *   label, detail   button text
 *   requires        condition that must be met (otherwise shown locked with 'lock' text)
 *   once            flag name; the choice can only be used one time
 *   outcomes        list; the FIRST outcome whose 'if' matches is used (last has no 'if')
 *
 * OUTCOME FIELDS
 *   text, give[], take[], set[], unset[], health, insight, next, ending
 *   luck => ['chance' => N, 'win' => [...], 'lose' => [...]]
 */

function game_items(): array
{
    return [
        'revolver'   => ['name' => 'Revolver',    'emoji' => "\u{1F52B}", 'points' => 10, 'desc' => 'Skipped countless meals for this gun and its single bullet.'],
        'knife'      => ['name' => 'Knife',       'emoji' => "\u{1F52A}", 'points' => 10, 'desc' => 'Found thrust through some poor bastard’s chest. Cuts through gold.'],
        'gold_chain' => ['name' => 'Gold Chain',  'emoji' => "\u{26D3}\u{FE0F}", 'points' => 50, 'desc' => 'Heavy gold. Worth more than a month of rent.'],
        'chips'      => ['name' => 'Casino Chips', 'emoji' => "\u{1FA99}", 'points' => 20, 'desc' => 'A stack of chips. Spend them at the tables.'],
        'cash'       => ['name' => 'Roll of Cash', 'emoji' => "\u{1F4B5}", 'points' => 40, 'desc' => 'Won at the roulette table.'],
    ];
}

function game_endings(): array
{
    $bad = 'You tried to escape the cycle. This district feeds off the blood of the weak and you are nourishment. There is no greater calling for scum like you, and like you rat you are. You die. Bleeding out like the dog you are.';
    return [
        'good' => [
            'title' => 'Enough', 'image' => 'end_good.svg', 'bonus' => 300,
            'alt' => 'An open vault full of cash with a lone figure sitting in the doorway',
            'text' => [
                'The attendant’s hands are steady for the first time as he turns the wheel. The door swings open on more money than you have ever seen, stacked and bundled like firewood.',
                'You stand in it and wait to feel something. Instead you think of the surgeon, the widow, the girl asleep on her chips, the man who would trade all of this for a quiet night. You spent your whole life chasing this room. Now that you’re standing in it, you can finally put the chase down.',
                'You let the revolver fall. The alarm is already ringing and you can hear boots in the corridor. You sit down on the cool floor, among the paper, and breathe. When the first shot comes it hurts less than you thought it would. The smell of the apartment is gone. You die the way almost no one in this district does: at peace, wanting nothing.',
            ],
        ],
        'bad' => [
            'title' => 'The Cycle', 'image' => 'end_bad.svg', 'bonus' => 0,
            'alt' => 'A dark alley floor with a spreading red puddle in the rain',
            'text' => [$bad],
        ],
        'assistant' => [
            'title' => 'The Assistant', 'image' => 'end_assistant.svg', 'bonus' => 200,
            'alt' => 'Two silhouettes in a limousine, one carrying a briefcase',
            'text' => [
                'He doesn’t ask what you were really looking for downstairs. He just says you’re better company than his last three assistants, and you say yes.',
                'It turns out he needs very little: someone to carry his coat, call his car, and laugh at the right moments. He pays you more in a month than the apartment ever cost you in a year. You move out. The stench stays behind, though some nights you swear you can still smell it on your collar.',
                'He introduces you at the tables as a friend, and the strange part is that he means it. The district is still out there, feeding off the weak, but from the back seat of a limousine it looks like a different city. You don’t ask what the money costs. You just keep your hands dry.',
            ],
        ],
    ];
}

function game_scenes(): array
{
    return [

        /* ------------------------------------------------------------ 1 */
        'apartment' => [
            'title' => 'The Apartment', 'image' => 'apartment.svg', 'alt' => 'A cramped apartment with peeling wallpaper and rain on the window',
            'segments' => [
                ['text' => 'You wake up in your crummy apartment. Before the cacophony of poverty wakes you, the smell does. Your nostrils flare before your eyes slowly open.'],
                ['text' => 'There is a constant stench to this place. Even the wallpaper seems to peel from the smell. You’ve been here for a long time. Longer than you’d care to think about, yet your nose hasn’t grown use to the smell. It rejects it just as you mind does because the moment you accept the blend of sweat, blood, and mold as your consistent reality, is the moment it becomes your life.'],
                ['text' => 'You worry that the stench of the apartment, the people- that the stench of this lifestyle will cling to you and never let go. So today, you set off to make a change in your life.'],
            ],
            'choices' => [
                ['label' => 'Grab your revolver.', 'detail' => 'Bullets in this District cost a fortune. You’ve skipped countless meals to afford this gun and a single bullet.',
                    'outcomes' => [['text' => 'You check the chamber. One bullet. Just the one.', 'give' => ['revolver'], 'set' => ['bullet'], 'next' => 'alley']]],
                ['label' => 'Grab your knife.', 'detail' => 'You found this knife thrusted through some poor bastard’s chest.',
                    'outcomes' => [['text' => 'The blade is cold, and the handle is still stained.', 'give' => ['knife'], 'next' => 'alley']]],
                ['label' => 'Bring both weapons.', 'detail' => 'Better safe than sorry.',
                    'outcomes' => [['text' => 'You tuck the knife in your boot and the revolver in your waistband.', 'give' => ['revolver', 'knife'], 'set' => ['bullet'], 'next' => 'alley']]],
                ['label' => 'Leave the apartment unequipped.',
                    'outcomes' => [['text' => 'You leave with nothing but the clothes on your back.', 'next' => 'alley']]],
            ],
        ],

        /* ------------------------------------------------------------ 2 */
        'alley' => [
            'title' => 'The Alley', 'image' => 'alley.svg', 'alt' => 'A rainy alley with balconies and a man in a suit sheltering under one',
            'segments' => [
                ['text' => 'After you descend the staircase, that has been the cause of injuries for many of the tenants who live here, you find yourself outside. And as if the city is trying to taunt you for an attempt at a better life, it begins to rain. As you make your way quickly cut through an alley that provides poor coverage through its poor excuses of balconies you also see someone taking refuge nearby as well. He looks out of place. A suit and tie, leather shoes, a gold chain, and a watch that occasionally refracts the lights of the district back into your eyes. Could this save you the trouble of robbing that gambling den?'],
                ['if' => ['has' => ['revolver']], 'text' => 'Your revolver sits heavy in your waistband, with its one bullet.'],
                ['if' => ['has' => ['knife'], 'not_has' => ['revolver']], 'text' => 'The knife is cold against your ribs.'],
                ['if' => ['not_has' => ['revolver', 'knife']], 'text' => 'Your hands are empty, and the rain makes them feel colder.'],
            ],
            'choices' => [
                ['label' => 'Rob him.',
                    'outcomes' => [
                        ['if' => ['has' => ['revolver', 'knife']], 'text' => 'You pull out your firearm and fire your bullet to the side of the man’s head. A bluff you can afford since you still have your knife. His hands shake as he unclasps the gold chain and holds it out. You snatch it and run before the echo dies, the rain soaking you through.',
                            'give' => ['gold_chain'], 'unset' => ['bullet'], 'set' => ['look_wet'], 'next' => 'den'],
                        ['if' => ['has' => ['revolver']], 'text' => 'You brandish your firearm and bark an order at the man. “Anything of value, hand it over!” The man looks at you and calls your bluff. “It would be foolish of me to believe that your gun has any bullets in it. You barely look like you could afford breakfast.” The man reaches into his jacket as if he is about to pull out a weapon and you shoot him in the chest. As he staggers backwards you hear shouting from around the corner closest to the main street: “Sounds like the boss is over here!” You run out of the alley the way you came, getting drenched by the rain and wielding an empty firearm. Your only bullet gone.',
                            'unset' => ['bullet'], 'set' => ['look_wet', 'shot_man'], 'next' => 'den'],
                        ['if' => ['has' => ['knife']], 'text' => 'You get closer to the man and hold out your knife. “Anything of value, hand it over!” He looks at the blade, then at you, and slowly unclasps his gold chain. “Take it. Take it.” You run before his bodyguards turn the corner, the rain soaking you through.',
                            'give' => ['gold_chain'], 'set' => ['look_wet'], 'next' => 'den'],
                        ['text' => 'You lie to the man, saying that you have a knife. He doesn’t buy it. He doesn’t even raise his voice. One hard shove into the brick, a boot in your ribs, and he steps over you into the rain. You limp away, soaked.',
                            'health' => -25, 'set' => ['look_wet'], 'next' => 'den'],
                    ]],
                ['label' => 'Ask for directions.',
                    'outcomes' => [['text' => 'You politely ask the man for directions to the gambling den, although you already know where it is. He tells you that he is headed to the same gambling den and offers you a ride with a innocently naive smile. He surely can’t be from around here. After a few seconds large bodyguards turn the corner as the man’s limousine pulls outside of the alley’s exit. You accept his offer, scared of what would happen if you say no, and get into his limo.',
                        'set' => ['with_rich_man', 'look_dry'], 'next' => 'den']]],
                ['label' => 'Walk past him.',
                    'outcomes' => [['text' => 'You walk past the man and sprint through alleyway to alleyway trying to stay as dry as possible.',
                        'set' => ['look_damp'], 'next' => 'den']]],
                ['label' => 'Run past him while snatching his chain.',
                    'outcomes' => [
                        ['if' => ['has' => ['knife']], 'text' => 'If you have a knife then it will cut through the chain. As you run past the ruffian you snatch the chain while running your knife through it and it comes loose. He shouts some obscenities at you as you turn the corner sprinting and not looking back.',
                            'give' => ['gold_chain'], 'set' => ['look_wet'], 'next' => 'den'],
                        ['text' => 'As you run past the man trying to grab his chain you yank it hard, but it does not come loose. His elbow finds your face before you can let go, and you stagger away into the rain, soaked and bleeding.',
                            'health' => -20, 'set' => ['look_wet'], 'next' => 'den'],
                    ]],
            ],
        ],

        /* ------------------------------------------------------------ 3 */
        'den' => [
            'title' => 'The Gambling Den', 'image' => 'den.svg', 'alt' => 'A gambling den with red pagoda roofs and a golden dragon over the entrance',
            'on_enter' => [
                ['if' => ['flag' => ['look_wet'], 'has_any' => ['revolver', 'knife']], 'take' => ['revolver', 'knife'], 'set' => ['patted_down', 'lost_weapons']],
                ['if' => ['flag' => ['look_wet'], 'not_flag' => ['lost_weapons']], 'set' => ['patted_down']],
            ],
            'segments' => [
                ['if' => ['seen' => false], 'text' => 'You find yourself in front of the gambling den. Places like this aren’t legal, but the muted blue eyed bastards hovering over this city don’t care about the scum at the bottom. The building was impressively designed, with red accents on pagodas and a golden dragon hanging over the entrance.'],
                ['if' => ['seen' => false, 'flag' => ['with_rich_man']], 'text' => 'You’re with the rich limousine man, so you get in with surprising ease. He must be a regular. Nobody so much as looks at your pockets.'],
                ['if' => ['seen' => false, 'flag' => ['look_damp']], 'text' => 'You get strange looks from the other patrons filing in, a soaked rat among the silk. The doorman eyes your clothes and decides you’re not worth the trouble. He waves you through.'],
                ['if' => ['seen' => false, 'flag' => ['lost_weapons']], 'text' => 'You get strange looks from the other patrons entering the building, and the doormen don’t like what they see. They pat you down, hard, and take every weapon you were carrying. They drop them in a bin behind the desk and wave you through.'],
                ['if' => ['seen' => false, 'flag' => ['patted_down'], 'not_flag' => ['lost_weapons']], 'text' => 'You get strange looks from the other patrons entering the building. The doormen pat you down and find nothing but a soaked, empty pocket. One of them laughs and waves you through.'],
                ['if' => ['seen' => true], 'text' => 'You’re back on the casino floor. Nothing has changed but the faces.'],
                ['text' => 'Once inside you immediately hear the dinging of slot machines, the sobs of people who lost more than they could afford, the cheering of a select few that lady luck smiled upon at roulette machines. You’re not here for any of the games. You’re here for much more than dumb luck. You’re looking for the safe. Desperation and madness has drove you here without a concrete plan, and now you have to make your own luck.'],
            ],
            'choices' => [
                ['label' => 'Trade in the gold chain for chips.', 'detail' => 'Lose the gold chain and gain chips. This opens up the gambling tables.',
                    'requires' => ['has' => ['gold_chain']], 'lock' => 'Requires a gold chain.',
                    'outcomes' => [['text' => 'You slide the chain across the cashier’s cage. He weighs it, shrugs, and pushes a stack of chips back at you.', 'take' => ['gold_chain'], 'give' => ['chips'], 'next' => 'tables']]],
                ['label' => 'Follow an attendant to the back.', 'detail' => 'If you have a gun or a knife you can discreetly press it into their back and tell them to play it cool.',
                    'outcomes' => [
                        ['if' => ['has_any' => ['revolver', 'knife']], 'text' => 'You pick out an attendant heading for the staff door and fall in behind him. You press your weapon into his back and murmur, “Play it cool.” He does. Nobody on the floor notices a thing.',
                            'set' => ['forced_attendant'], 'next' => 'back_hall'],
                        ['text' => 'You follow an attendant toward the staff door with nothing in your hands but nerve. A man in a black vest steps out of nowhere and drives a fist into your gut. “Staff only.” You’re dragged back to the floor and dropped.',
                            'health' => -40, 'next' => 'den'],
                    ]],
                ['label' => 'Attempt to rob someone who just hit it big.',
                    'outcomes' => [['text' => 'You go after someone who just hit it big. No matter how it starts, it ends the same way: security takes care of you. They knock you out, drag you to the back, and take your organs. It’s house policy for people who can’t pay what they owe.', 'ending' => 'bad']]],
                ['label' => 'Ask the rich man you arrived with to spare you some chips.',
                    'requires' => ['flag' => ['with_rich_man']], 'lock' => 'Requires arriving with the rich man.',
                    'outcomes' => [['text' => 'You catch the rich man’s eye across the floor. He laughs, tosses you a stack of chips like it’s nothing, and nods toward a booth above the tables.', 'give' => ['chips'], 'set' => ['friend_started'], 'next' => 'lounge']]],
            ],
        ],

        /* ------------------------------------------------------------ 4 */
        'tables' => [
            'title' => 'The Tables', 'image' => 'tables.svg', 'alt' => 'A roulette wheel spinning under a green felt table',
            'segments' => [
                ['text' => 'The cage clerk waves you toward the roulette table. The wheel is a blur of red and black, and the faces around it are a blur of everything else. Everybody here is chasing something. For a moment, you are too.'],
                ['if' => ['has' => ['chips']], 'text' => 'The chips are warm in your hand.'],
                ['if' => ['not_has' => ['chips']], 'text' => 'Your hands are empty now.'],
            ],
            'choices' => [
                ['label' => 'Put every chip on red.',
                    'requires' => ['has' => ['chips']], 'lock' => 'Requires chips.',
                    'outcomes' => [['take' => ['chips'], 'luck' => ['chance' => 50,
                        'win' => ['text' => 'The wheel clicks, slows, and drops on red. The dealer pushes a roll of cash toward you without looking up.', 'give' => ['cash']],
                        'lose' => ['text' => 'The wheel clicks, slows, and drops on black. The dealer sweeps your chips away without looking up.']]]]],
                ['label' => 'Watch the people around the table.',
                    'once' => 'watched_tables',
                    'outcomes' => [['text' => 'A man in scrubs bets his patients’ bills on every spin. A widow in black feeds the wheel her husband’s pension and smiles when it takes it. Two kids in matching jackets scream at red like it owes them money. It hits you when the wheel stops: none of them look like they’re having fun, and all of them look like they have somewhere better to be.', 'insight' => 25]]],
                ['label' => 'Listen to what the dealer has to say.',
                    'once' => 'dealer_story',
                    'outcomes' => [['text' => '“Eleven years,” the dealer says quietly, still dealing. “I’ve seen a man win a house and lose it before sunrise. I’ve seen a woman win a hundred dollars and cry because it was enough to leave. Money doesn’t change people. It just speeds up whatever they already were.”', 'insight' => 25]]],
                ['label' => 'Head back to the floor.',
                    'outcomes' => [['text' => 'You step away from the table.', 'next' => 'den']]],
            ],
        ],

        /* ------------------------------------------------------------ 5 */
        'lounge' => [
            'title' => 'The High-Roller Lounge', 'image' => 'lounge.svg', 'alt' => 'A velvet booth above a casino floor with two glasses on the table',
            'segments' => [
                ['text' => 'The rich man’s booth sits above the floor behind a rope of red velvet. He waves off the host and pours you a drink like you’ve known each other for years. Up close he’s younger than you thought, and more tired. “Everyone down there thinks I’m the lucky one,” he says. “Sit.”'],
                ['if' => ['flag' => ['bonded']], 'text' => 'He keeps your glass full and keeps glancing at you like a man who has found someone to talk to.'],
            ],
            'choices' => [
                ['label' => 'Accept his offer to work for him.',
                    'requires' => ['flag' => ['bonded']], 'lock' => 'Requires spending time with him first.',
                    'outcomes' => [['text' => 'You say yes before he finishes asking.', 'ending' => 'assistant']]],
                ['label' => 'Listen to his stories.',
                    'once' => 'bonded',
                    'outcomes' => [['text' => 'He talks for an hour. A father who left him everything but time. A wife who liked the car more than him. A house with eleven bedrooms and one he actually sleeps in. You find yourself laughing, then he laughs, and the strange thing is that neither of you is faking it.', 'insight' => 25, 'set' => ['bonded']]]],
                ['label' => 'Ask him about the vault.',
                    'once' => 'asked_vault',
                    'outcomes' => [['text' => 'He smiles into his glass. “Everything in there is just paper with a bodyguard. I’d trade it for a quiet night.” He sounds like he means it.', 'insight' => 25]]],
                ['label' => 'Excuse yourself back to the floor.',
                    'outcomes' => [['text' => 'He nods, as if he was expecting it.', 'next' => 'den']]],
            ],
        ],

        /* ------------------------------------------------------------ 6 */
        'back_hall' => [
            'title' => 'The Staff Corridor', 'image' => 'back_hall.svg', 'alt' => 'A dim yellow corridor with cameras and a row of doors',
            'segments' => [
                ['text' => 'The staff door closes behind you and the noise of the casino drops to a hum. The corridor is lit in tired yellow, lined with doors and cameras that point at the floor and not at you. The attendant’s hands are shaking at his sides.'],
                ['if' => ['has' => ['revolver']], 'text' => 'Your revolver is pressed to his spine. You haven’t spoken above a whisper.'],
                ['if' => ['has' => ['knife'], 'not_has' => ['revolver']], 'text' => 'Your knife is pressed to his spine. You haven’t spoken above a whisper.'],
            ],
            'choices' => [
                ['label' => 'Listen to the attendant.',
                    'once' => 'attendant_story',
                    'outcomes' => [['text' => 'He starts talking just to fill the quiet. He knows the regulars by their losses: the surgeon, the widow, a senator’s son who tips in hundreds. He knows the winners too, and says they’re the saddest of all. “They come back to win again,” he says. “Nobody ever wins enough.”', 'insight' => 25]]],
                ['label' => 'Look through the one-way glass.',
                    'once' => 'oneway_glass',
                    'outcomes' => [['text' => 'Through the mirrored glass the high-roller room is laid out like an aquarium. A man buys a stranger’s drink to feel like a king. A couple in evening wear don’t speak the whole night. A girl no older than twenty rests her head on a pile of chips and falls asleep. Money bought every one of them something. None of them looks like they got what they paid for.', 'insight' => 25]]],
                ['label' => 'Make him take you to the vault.',
                    'outcomes' => [['text' => '“The vault,” you whisper. His eyes close. He starts walking.', 'next' => 'vault']]],
                ['label' => 'Let him go and slip back to the floor.',
                    'outcomes' => [['text' => 'You let him go. He doesn’t look back.', 'next' => 'den']]],
            ],
        ],

        /* ------------------------------------------------------------ 7 */
        'vault' => [
            'title' => 'The Vault', 'image' => 'vault.svg', 'alt' => 'A massive round steel vault door with a wheel at its center',
            'segments' => [
                ['text' => 'The vault door is a slab of steel as wide as a car, with a wheel in the middle like the helm of a ship. The cameras here point at you. The attendant is sweating through his vest, and every second you stand here is a second someone might notice.'],
                ['if' => ['min' => ['insight' => 75]], 'text' => 'You can’t stop thinking about everyone you passed on the way here. You’re not sure the money behind this door was ever what you were looking for.'],
            ],
            'choices' => [
                ['label' => 'Make the attendant open the vault.',
                    'requires' => ['has' => ['revolver']], 'lock' => 'Requires a revolver.',
                    'outcomes' => [
                        ['if' => ['min' => ['insight' => 75]], 'text' => 'You press the revolver to his back and tell him to open it.', 'ending' => 'good'],
                        ['text' => 'The door swings open on more money than you have ever seen, and you can’t stop yourself. You stuff your arms full. By the time you hear the alarm, three men are already behind you.', 'ending' => 'bad'],
                    ]],
                ['label' => 'Cut the key card from his neck and open it yourself.',
                    'requires' => ['has' => ['knife']], 'lock' => 'Requires a knife.',
                    'outcomes' => [['text' => 'You cut the lanyard and tear the card free. The door unlocks with a clunk, and the alarm goes off with it.', 'ending' => 'bad']]],
                ['label' => 'Step back from the door.',
                    'outcomes' => [['text' => 'You lower your weapon and back away from the wheel.', 'next' => 'back_hall']]],
                ['label' => 'Ask the attendant who the money is for.',
                    'once' => 'vault_ask',
                    'outcomes' => [['text' => '“Nobody,” he says. “It sits here. Every night I count it, and every night it’s still for nobody.”', 'insight' => 25]]],
            ],
        ],
    ];
}

/** Every flag name the story can set (used to validate save files). */
function game_known_flags(): array
{
    $flags = [];
    $collect = static function (array $outcome) use (&$flags, &$collect): void {
        foreach (['set', 'unset'] as $key) {
            foreach ($outcome[$key] ?? [] as $flag) {
                $flags[] = $flag;
            }
        }
        foreach (['win', 'lose'] as $side) {
            if (isset($outcome['luck'][$side])) {
                $collect($outcome['luck'][$side]);
            }
        }
    };
    foreach (game_scenes() as $scene) {
        foreach ($scene['on_enter'] ?? [] as $rule) {
            $collect($rule);
        }
        foreach ($scene['choices'] as $choice) {
            if (!empty($choice['once'])) {
                $flags[] = $choice['once'];
            }
            foreach ($choice['outcomes'] as $outcome) {
                $collect($outcome);
            }
        }
    }
    return array_values(array_unique($flags));
}
