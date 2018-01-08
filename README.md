# phpBB 3.1 Extension - Hookup to HJW Calendar Bridge

This extension bridges the `gn36/hookup` extension with `hjw/calendar`. Thus it allows finding dates using the hookup and then once a date has been found, it will be automatically added to the calendar, including the list of users that was entered into the hookup. Upon reopening the hookup, the active date is also automatically removed from the calendar again until a new date is found.

**Please note:** This extension is currently **unfinished and abandoned**. You can use it as a demonstration how to bundle the hookup extension with another calendar extension. You can therefore use it as a base for your own bridge. If you wish to use the `hjw/calendar` extension together with `gn36/hookup`, you can also take over the development of this extension on your own. If you wish to do so, I will be happy to help you get started.

If you can live with the shortcomings of the current version and *do not mind the risk of using an unfinished extension*, you can also try if this extension works for you the way it is. Beware, you will likely find bugs. If you still wish to use the extension, please mind at least [Hookup issue#56](https://github.com/gn36/phpbb-ext-hookup/issues/56). But be aware that you will have to fix upcoming problems yourself.

Currently, the extension supports the following features:
1. Enter a date that was set as the active date into the calendar and remove it again when the active date is reset (-> See [event/listener.php function activedate_set](https://github.com/gn36/phpbb-ext-hjw-bridge/blob/80733c2b1a2521db40d61ac76b6d7f9ee769d276/event/listener.php#L115))
2. Synchronize the hookup participants from the hookup to the calendar (-> See [event/listener.php function sync_participant_hookup](https://github.com/gn36/phpbb-ext-hjw-bridge/blob/80733c2b1a2521db40d61ac76b6d7f9ee769d276/event/listener.php#L239))
3. Synchronize the calendar participants from the calendar to the hookup (-> See [event/listener.php function sync_participant_calendar](https://github.com/gn36/phpbb-ext-hjw-bridge/blob/80733c2b1a2521db40d61ac76b6d7f9ee769d276/event/listener.php#L308))

The first two features use events in the hookup extension:
1. `gn36.hookup.set_activedate_confirmed`
2. `gn36.hookup.viewtopic.process_status`

The third feature uses an event in the calendar:

3. `hjw.calendar.viewtopic.modify_participants_list`

There are currently also a few shortcomings that should probably be fixed:
1. The hookup and the calendar use different concepts for storing appointments. 
   - The calendar supports events that are not connected to a topic. It is difficult to convert these into a hookup.
   - The calendar can store date ranges (start and end), the hookup only supports single dates
   - The hookup supports also dateless entries (e.g. "Apple", "Strawberry", ...), this cannot be synchronized in a useful way to the calendar
   - The hookup supports an arbitrary number of date entries per topic. The calendar only supports one date per topic.
   - The calendar supports arbitrary repeating entries and special days. The hookup supports creating new entries on a weekly basis and deleting old ones in the same go. Both are not compatible.
2. The hookup can be used in multiple ways, which require different synchronizations to the calendar:
   - The hookup can be used to propose several dates to multiple people, but the meeting takes place only on one of them. Once, a date has been decided, this date should be entered to the calendar (this is the current implementation).
   - The hookup can be used to propose several dates to multiple people, a meeting will take place on each of them. People can pick in which they wish to participate. In this case, all dates should be synchronized to the calendar, each with their own participant list (currently not supported by either the calendar nor this extension)
   - The hookup can be used with date-like text entries mixed with dates. These face the same restrictions as the above two bullet points, in addition an interpretation of the text entries is needed for conversion to a date that could be entered into the calendar.
3. The extension is not finished (see e.g. `die`, and `echo` commands).
4. The extension is not checking for its dependencies (e.g. checking whether hookup and calendar are actually installed).
 

## Installation

Install and activate the gn36/hookup extension and the hjw/calendar extension.

Clone into ext/gn36/hjw_bridge:

    git clone https://github.com/gn36/phpbb-ext-hjw-bridge ext/gn36/hjw_bridge

Go to "ACP" > "Customise" > "Extensions" and enable the "Calendar/Hookup bridge" extension.

## Development

This extension is abandoned. Feel free to take over development (see above)!

## Automated Testing

No tests available.

## License

[GPLv2](license.txt)
