This cleanup hook requires the Saxon HE processor. 
Follow the installation instructions, and place the file saxon9he.jar in your top-level MIK folder. 
http://www.saxonica.com/html/documentation/about/gettingstarted/gettingstartedjava.html

Cleanup hooks run after files have been written individually (within the main loop) 
and just before mik exits.

When running the xslt postwritehook against large collections, your system may get bogged
down with the number of background saxon processes (1 per MODS file!). Launching
Saxon outside of the main loop lets you leverage its batch mode (process entire 
directories), reducing the cost to the system.

To enable cleanup hooks, add a section to your config called `[CLEANUP]`,
and for every script you'd like to run, add it to the 'scripts' array:

~~~

[CLEANUP]
scripts[] = path/to/my/cleanup/script

~~~

Note that apply_xslt_with_saxon.php requires an `[XSLT]` section in the config.
