<?php

namespace Tests\Browser;

use Laravel\Dusk\Browser;
use Tests\DuskTestCase;

class ChatInterfaceTest extends DuskTestCase
{
    /**
     * Test that the chat interface loads correctly.
     */
    public function test_chat_interface_loads()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->waitFor('#app')
                ->assertSee('Weather Forecaster')
                ->assertSee('Ask about weather in any location, any time!');
        });
    }

    /**
     * Test that a user can send a message and receive a response.
     */
    public function test_user_can_send_message()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->waitFor('.chat-input-container')
                ->type('input[placeholder="Type your weather question..."]', 'What is the weather in London?')
                ->press('@send-button')
                ->waitFor('.typing-indicator', 10)
                ->waitUntilMissing('.typing-indicator', 30)
                ->assertSee('London');
        });
    }

    /**
     * Test that the location detection works.
     */
    public function test_location_detection()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->waitFor('.chat-container')
                ->waitForText('I\'ve detected that you\'re near', 30)
                ->assertSee('Here\'s the current weather');
        });
    }

    /**
     * Test that the chat can be cleared.
     */
    public function test_chat_can_be_cleared()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->waitFor('.chat-container')
                ->click('@clear-chat-button')
                ->waitForDialog()
                ->acceptDialog()
                ->assertDontSee('I\'ve detected that you\'re near');
        });
    }

    /**
     * Test that the swipe gestures work.
     */
    public function test_swipe_gestures()
    {
        $this->browse(function (Browser $browser) {
            $browser->visit('/')
                ->waitFor('.chat-container')
                ->script([
                    "document.querySelector('.chat-container').dispatchEvent(new TouchEvent('touchstart', {
                            bubbles: true,
                            touches: [{ clientX: 300, clientY: 300 }]
                        }));"
                ])
                ->script([
                    "document.querySelector('.chat-container').dispatchEvent(new TouchEvent('touchmove', {
                            bubbles: true,
                            touches: [{ clientX: 100, clientY: 300 }]
                        }));"
                ])
                ->script([
                    "document.querySelector('.chat-container').dispatchEvent(new TouchEvent('touchend', {
                            bubbles: true
                        }));"
                ])
                ->waitForDialog()
                ->assertDialogOpened('Swipe detected!')
                ->acceptDialog();
        });
    }

    /**
     * Test that the application works offline.
     */
    public function test_offline_mode()
    {
        $this->browse(function (Browser $browser) {
            // First make a request to cache it
            $browser->visit('/')
                ->waitFor('.chat-input-container')
                ->type('input[placeholder="Type your weather question..."]', 'What is the weather in Paris?')
                ->press('@send-button')
                ->waitUntilMissing('.typing-indicator', 30)
                ->assertSee('Paris');

            // Now go offline and try to use cached data
            $browser->script("window.dispatchEvent(new Event('offline'))");
            $browser->waitForText('You are offline', 10);

            // Try to use cached data
            $browser->type('input[placeholder="Type your weather question..."]', 'What is the weather in Paris?')
                ->press('@send-button')
                ->waitForText('cached data', 10)
                ->assertSee('cached data');

            // Go back online
            $browser->script("window.dispatchEvent(new Event('online'))");
            $browser->waitForText('You are back online', 10);
        });
    }

    /**
     * Test responsive design on different screen sizes.
     */
    public function test_responsive_design()
    {
        $this->browse(function (Browser $browser) {
            // Test on mobile size
            $browser->resize(375, 667)
                ->visit('/')
                ->waitFor('.chat-container')
                ->assertVisible('.chat-input-container')
                ->assertPresent('.stardew-paper');

            // Test on tablet size
            $browser->resize(768, 1024)
                ->refresh()
                ->waitFor('.chat-container')
                ->assertVisible('.chat-input-container');

            // Test on desktop size
            $browser->resize(1366, 768)
                ->refresh()
                ->waitFor('.chat-container')
                ->assertVisible('.chat-input-container');
        });
    }
}
