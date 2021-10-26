# Release Instructions

1. Pull down the latest changes on the current stable branch
2. Update and commit the [CHANGELOG.md](./CHANGELOG.md) file
3. Compile the front-end assets and commit them

```zsh
npm install && npm run prod
```

4. Tag a new version of the package
5. Push all commits and the new tag to GitHub
6. Create a new GitHub release with the same release notes from CHANGELOG.md
