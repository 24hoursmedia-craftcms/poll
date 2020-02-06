# Poll Changelog

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
