<?php
declare(strict_types=1);

/**
 * Story data for Veilbound: items, endings and the nine story pages.
 * Every page has exactly four choices. Choice metadata drives both the game
 * rules (actions.php) and the consequence previews (game.php).
 *
 * Choice keys:
 *   label    text on the button
 *   next     scene id to move to (defaults to the same scene)
 *   give     item id granted (choice is unavailable once the item is owned)
 *   requires item ids that must be owned
 *   once     flag name; the choice can only be used one time
 *   aether / resolve  stat changes applied on success
 *   check    ['power' => N, 'fail_next', 'fail_resolve', 'fail_note', 'fail_ending']
 *            success when aether + owned item power >= N
 *   ending   'A' | 'B' | 'C' ends the game
 *   note     message shown after the choice succeeds
 */

function game_items(): array
{
    return [
        'warding_salt'   => ['name' => 'Warding Salt',    'power' => 15, 'desc' => 'Blessed salt that repels Hollows. Adds 15 power in fights.'],
        'silver_lantern' => ['name' => 'Silver Lantern',  'power' => 20, 'desc' => 'An Order lantern that holds Hollows at bay. Adds 20 power and is needed for the Sealing Rite.'],
        'sigil_chalk'    => ['name' => 'Sigil Chalk',     'power' => 0,  'desc' => 'Breaks wards and proves you know the old sigils. Opens the archive cabinet.'],
        'mentors_ledger' => ['name' => "Mentor's Ledger", 'power' => 10, 'desc' => 'Your mentor\'s notes on the relic. Adds 10 power and earns trust from others.'],
        'veil_key'       => ['name' => 'Veil Key',        'power' => 0,  'desc' => 'Opens the sealed stairs to the Basement Archive.'],
        'binding_rune'   => ['name' => 'Binding Rune',    'power' => 0,  'desc' => 'A rune that can lock the relic away. Needed for the Sealing Rite.'],
    ];
}

function game_endings(): array
{
    return [
        'A' => ['title' => 'The Veil Holds', 'image' => 'end_a.svg', 'alt' => 'Dawn light over a resealed glowing relic',
                'text' => 'The relic hums, dims, and locks. The Hollows dissolve into morning light and the Meridian Arcade falls silent. The Lantern Order will call you a sorcerer, not a rookie.', 'base' => 500],
        'B' => ['title' => 'Hollow Crown', 'image' => 'end_b.svg', 'alt' => 'A purple crown of dark energy floating above a silhouette',
                'text' => 'Power floods through you, far more than the Order ever allowed. The Hollows kneel. The city is quiet now, and you are no longer sure it is still yours.', 'base' => 300],
        'C' => ['title' => 'The Lantern Goes Dark', 'image' => 'end_c.svg', 'alt' => 'An extinguished lantern lying on a dark floor',
                'text' => 'Your light gutters and dies. The Hollows pour out into the night, and the Order will need another sorcerer, one who is more prepared than you were.', 'base' => 100],
    ];
}

function game_scenes(): array
{
    return [
        'rooftop' => [
            'title' => 'Rooftop Briefing', 'image' => 'rooftop.svg', 'alt' => 'City skyline at dusk seen from a rooftop',
            'text' => 'Rain hammers the rooftop high above the city. Your mentor\'s voice crackles over the Order radio: the sealed relic in the Meridian Arcade has been breached, and Hollows are leaking into the streets. You are the closest sorcerer. How do you prepare?',
            'choices' => [
                ['label' => 'Visit the Order Armory', 'next' => 'armory'],
                ['label' => 'Take the back alley shortcut', 'next' => 'alley'],
                ['label' => 'Call your mentor', 'next' => 'mentor'],
                ['label' => 'Go straight to the arcade doors', 'next' => 'lobby', 'resolve' => -5, 'note' => 'You rush in unprepared. Your pulse is already racing.'],
            ],
        ],
        'armory' => [
            'title' => 'Order Armory', 'image' => 'armory.svg', 'alt' => 'Weapon racks and a glowing sigil circle',
            'text' => 'The armory smells of cold iron and candle wax. Racks of charms, blades, and sealed jars line the walls, and a sigil circle glows faintly in the floor.',
            'choices' => [
                ['label' => 'Take the Warding Salt', 'give' => 'warding_salt', 'note' => 'You pocket a pouch of blessed salt.'],
                ['label' => 'Take the Silver Lantern', 'give' => 'silver_lantern', 'note' => 'The lantern flares warmly in your hand.'],
                ['label' => 'Study the weapons wall', 'once' => 'studied_weapons', 'aether' => 10, 'note' => 'You trace the old techniques until they click. Your aether grows.'],
                ['label' => 'Leave for the arcade', 'next' => 'lobby'],
            ],
        ],
        'alley' => [
            'title' => 'Back Alley', 'image' => 'alley.svg', 'alt' => 'A neon-lit alley with graffiti and a dumpster',
            'text' => 'Neon buzzes over a narrow alley. Some of the graffiti on the brick is moving, glowing along a path toward the far end. A rusty service door leads into the arcade.',
            'choices' => [
                ['label' => 'Search the dumpsters', 'give' => 'sigil_chalk', 'note' => 'Under a pile of trash you find a stick of sigil chalk.'],
                ['label' => 'Follow the glowing graffiti', 'next' => 'rival'],
                ['label' => 'Return to the rooftop', 'next' => 'rooftop'],
                ['label' => 'Slip through the service door', 'next' => 'lobby', 'resolve' => -10, 'note' => 'The door scrapes your arm on the way through.'],
            ],
        ],
        'mentor' => [
            'title' => "Mentor's Call", 'image' => 'mentor.svg', 'alt' => 'A glowing phone screen in a dark room',
            'text' => 'Your mentor answers on the first ring. Her voice is calm, but you can hear alarms behind her. "Listen carefully. We do not have much time."',
            'choices' => [
                ['label' => 'Ask about the relic', 'give' => 'mentors_ledger', 'note' => 'She sends you her ledger of notes on the relic.'],
                ['label' => 'Ask for a blessing', 'once' => 'blessing', 'aether' => 15, 'note' => 'She whispers an old protective rite. Your aether surges.'],
                ['label' => 'Ask where the vault is', 'once' => 'vault_clue', 'aether' => 5, 'note' => '"Beneath the arcade, behind a door that needs a Veil Key."'],
                ['label' => 'Hang up', 'next' => 'rooftop'],
            ],
        ],
        'lobby' => [
            'title' => 'Arcade Lobby', 'image' => 'lobby.svg', 'alt' => 'A dim arcade lobby with rows of old cabinets',
            'text' => 'The Meridian Arcade lobby is thick with dust. Dead cabinets flicker, and something shuffles in the Food Court to the left. A sealed staircase leads down.',
            'choices' => [
                ['label' => 'Enter the Food Court', 'next' => 'foodcourt'],
                ['label' => 'Take the stairs down', 'requires' => ['veil_key'], 'next' => 'archive'],
                ['label' => 'Search the claw machines', 'once' => 'claw_machines', 'aether' => 10, 'note' => 'A charm tucked in a plush toy restores some aether.'],
                ['label' => 'Retreat outside', 'next' => 'rooftop'],
            ],
        ],
        'foodcourt' => [
            'title' => 'Haunted Food Court', 'image' => 'foodcourt.svg', 'alt' => 'A swarm of glowing Hollow spirits over empty tables',
            'text' => 'Abandoned tables sit under flickering signs, and a swarm of Hollows drifts between them. One of them clutches a pale key.',
            'choices' => [
                ['label' => 'Fight the Hollows head-on', 'give' => 'veil_key', 'next' => 'lobby',
                    'check' => ['power' => 60, 'fail_next' => 'lobby', 'fail_resolve' => -25, 'fail_note' => 'The swarm overwhelms you and you stumble back to the lobby.'],
                    'note' => 'You break through and tear a Veil Key from the swarm.'],
                ['label' => 'Scatter Warding Salt', 'requires' => ['warding_salt'], 'give' => 'veil_key', 'next' => 'lobby', 'note' => 'The salt makes the Hollows shriek and scatter. You take the Veil Key.'],
                ['label' => 'Hold them back with the Silver Lantern', 'requires' => ['silver_lantern'], 'give' => 'veil_key', 'next' => 'lobby', 'aether' => 5, 'note' => 'The lantern pins the Hollows against the walls. You take the Veil Key.'],
                ['label' => 'Retreat to the lobby', 'next' => 'lobby'],
            ],
        ],
        'rival' => [
            'title' => 'The Rival', 'image' => 'rival.svg', 'alt' => 'A rival sorcerer standing in a chalk circle with a red glow',
            'text' => 'At the end of the alley, a sorcerer in a torn coat stands in a chalk circle. Mara Quill, a rival from the Order, dangles a Veil Key from two fingers. "Looking for this?"',
            'choices' => [
                ['label' => 'Duel Mara for the key', 'give' => 'veil_key', 'next' => 'lobby',
                    'check' => ['power' => 55, 'fail_next' => 'alley', 'fail_resolve' => -20, 'fail_note' => 'Mara outmaneuvers you and knocks you back into the alley.'],
                    'note' => 'You win the duel. Mara tosses you the key with a scowl.'],
                ['label' => 'Trade a sigil copy for the Veil Key', 'requires' => ['sigil_chalk'], 'give' => 'veil_key', 'next' => 'lobby', 'note' => 'Mara copies your sigil, impressed, and hands over the key.'],
                ['label' => 'Ask for her help', 'requires' => ['mentors_ledger'], 'once' => 'rival_help', 'give' => 'veil_key', 'aether' => 20, 'next' => 'lobby', 'note' => 'She recognizes your mentor\'s handwriting, shares her power, and gives you the key.'],
                ['label' => 'Walk away', 'next' => 'alley'],
            ],
        ],
        'archive' => [
            'title' => 'Basement Archive', 'image' => 'archive.svg', 'alt' => 'Tall shelves and a warded cabinet glowing in the dark',
            'text' => 'The sealed stairs open into a cold archive. Shelves of Order records stretch into the dark, and a warded cabinet pulses with violet light. A passage leads further down to the shrine.',
            'choices' => [
                ['label' => 'Take the Binding Rune', 'requires' => ['sigil_chalk'], 'give' => 'binding_rune', 'note' => 'Your chalk breaks the ward. The Binding Rune is yours.'],
                ['label' => 'Read the Order records', 'once' => 'order_records', 'aether' => 10, 'note' => 'The old records teach you how the relic was sealed. Your aether grows.'],
                ['label' => 'Descend to the shrine', 'next' => 'shrine'],
                ['label' => 'Go back up', 'next' => 'lobby'],
            ],
        ],
        'shrine' => [
            'title' => 'Shrine of the Relic', 'image' => 'shrine.svg', 'alt' => 'A glowing relic floating above a stone pedestal with rune rings',
            'text' => 'The relic floats above its pedestal, cracked and blazing. Rune rings spin around it, and a hulking Warden steps out of the light. This is the end of the road.',
            'choices' => [
                ['label' => 'Perform the Sealing Rite', 'requires' => ['binding_rune', 'silver_lantern'], 'ending' => 'A'],
                ['label' => "Take the relic's power", 'ending' => 'B'],
                ['label' => 'Fight the Warden', 'ending' => 'A',
                    'check' => ['power' => 70, 'fail_next' => 'shrine', 'fail_resolve' => -40, 'fail_ending' => 'C', 'fail_note' => 'The Warden\'s blow shatters your guard.']],
                ['label' => 'Flee the arcade', 'ending' => 'C'],
            ],
        ],
    ];
}

/** All one-time flag names used by choices (used to validate save files). */
function game_known_flags(): array
{
    $flags = [];
    foreach (game_scenes() as $scene) {
        foreach ($scene['choices'] as $choice) {
            if (!empty($choice['once'])) {
                $flags[] = $choice['once'];
            }
        }
    }
    return $flags;
}
