# Upgrading to v4

- You must run `valet` once for the upgrader to run
- Only works on PHP 8.0+
- Update custom drivers and SampleValetDriver:
    - Match the new type hints of the base ValetDriver
    - Extend the new namespaced drivers instead of the old globally-namespaced drivers
    - Add namespace
- Probably a lot more, @todo forgot to flesh this out as i went
