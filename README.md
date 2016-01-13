# phpBB 3.1 Extension - Hookup to HJW Calendar Bridge

This extension bridges the hookup extension with hjw/calendar. Thus it allows finding dates using the hookup and then once a date has been found, it will be automatically added to the calendar, including the list of users that was entered into the hookup. Upon reopening the hookup, the active date is also automatically removed from the calendar again until a new date is found.

## Installation

Clone into ext/gn36/hjw_bridge:

    git clone https://github.com/gn36/phpbb-ext-hjw-bridge ext/gn36/hjw_bridge

Go to "ACP" > "Customise" > "Extensions" and enable the "Calendar/Hookup bridge" extension.

## Development

If you find a bug, please report it on https://github.com/gn36/phpbb-ext-hjw-bridge

## Automated Testing

We use automated unit tests including functional tests to prevent regressions. Check out our travis build below:

master: [![Build Status](https://travis-ci.org/gn36/phpbb-ext-hjw-bridge.png?branch=master)](http://travis-ci.org/gn36/phpbb-ext-hjw-bridge)

## License

[GPLv2](license.txt)
