# Poll Changelog

## 2.0.1
### Changed
- Craft 4 compatibility release

## 1.7.0 - 2021-02-28
### Added
- Now it's possible to add user input text fields to answers. [see the docs](https://io.24hoursmedia.com/craftcms-poll/add-textual-user-input-to-poll-choices)

## 1.6.3 - 2021-02-09

### Fixed
- A potential block in gc/run (garbage cleanup) due to a Craft error is now ignored.

## 1.6.1 - 2020-10-28

### Fixed
- Fix problems on case sensitive file systems (i.e. linux vs osx/win)

## 1.6.0 - 2020-10-27

### Fixed
- composer v2 compatibility

## 1.5.0 - 2020-10-14

### Fixed
- Carft 3.5 compatibility fixes

## 1.2.5 - 2020-02-20

### Fixed
- Fixed problem viewing poll results in Admin CP

## 1.2.4 - 2020-02-09
### Fixed
- fixed installation button for content in craft 3.4

## 1.2.3 - 2020-02-09

### Added
- Added a PollSubmittedEvent to hook into caching or do external processing - see https://io.24hoursmedia.com/craftcms-poll/poll-events

### Modified
- When retrieving results, user count now refers to num users per answer instead of poll total
- Optimized database indices

## 1.2.2 - 2020-02-06

### Fixed
- Fixed ordering of users by participation date

## 1.2.1 - 2020-02-06

### Fixed
- MySQL 5.7 compatibility

## 1.2.0 - 2020-02-05

### Added
- Get participating users for a poll in twig/frontend
- Get user votes by answer in twig/frontent
- Added percentage in poll results by answer

### Modified
- Added a craft.poll variable that exposes public methods to manage and get data from a poll
- Added getResults and more to craft.poll, replacing legacy twig filters

## 1.1.2 - 2020-02-02

### Added
- Added control panel section for polls
- Download raw data for polls for marketing analysis / segmentation

## 1.0.3 - 2020-01-29

### Fixed
- Poll plugin blocked removal of other plugins

## 1.0.1 - 2020-01-29

### Modified
- Configuration options
- Safety block against accidental uninstall

### Modified
- [#1 Poll submissions will be deleted when a poll entry is deleted](https://github.com/24hoursmedia-craftcms/poll/issues/1)

## 1.0.0 - 2020-01-22
### Added
- Initial release
