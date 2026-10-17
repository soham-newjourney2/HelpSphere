# HelpSphere

## Running the automatic escalation (Immediate Food Donations)

Immediate donations are sent to 5 NGOs at a time. If nobody accepts within 7 minutes,
the next 5 are notified. This needs a background script:

1. Start Apache and MySQL in XAMPP.
2. Double-click `start_escalation.bat` and leave the window open while testing.

If XAMPP isn't installed at `C:\xampp`, edit the path inside `start_escalation.bat`.
Without this script the site still works, but donations are never forwarded to the next batch.
