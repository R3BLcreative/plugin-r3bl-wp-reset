module.exports = function (grunt) {
	// Project config
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		compress: {
			dist: {
				options: {
					archive: './dist/r3bl-wp-reset.zip',
					mode: 'zip',
				},
				files: [
					{ src: './includes/*.php', dest: 'r3bl-wp-reset/' },
					{ src: './README.md', dest: 'r3bl-wp-reset/' },
					{ src: './index.php', dest: 'r3bl-wp-reset/' },
					{ src: './r3bl-wp-reset.php', dest: 'r3bl-wp-reset/' },
					{ src: './LICENSE.txt', dest: 'r3bl-wp-reset/' },
				],
			},
		},
	});

	// Load grunt plugins
	grunt.loadNpmTasks('grunt-contrib-compress');

	// Register tasks
	grunt.registerTask('default', ['compress']);
};
