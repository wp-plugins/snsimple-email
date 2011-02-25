(function() {
        // Load plugin specific language pack
        //tinymce.PluginManager.requireLangPack('snSimpleEmail');

        tinymce.create('tinymce.plugins.snSimpleEmailPlugin', {
                /**
                 * Initializes the plugin, this will be executed after the plugin has been created.
                 * This call is done before the editor instance has finished it's initialization so use the onInit event
                 * of the editor instance to intercept that event.
                 *
                 * @param {tinymce.Editor} ed Editor instance that the plugin is initialized in.
                 * @param {string} url Absolute URL to where the plugin is located.
                 */
                init : function(ed, url) {
                        // Register the command so that it can be invoked by using tinyMCE.activeEditor.execCommand('mcesnSimpleEmail');
                        ed.addCommand('mcesnSimpleEmail', function() {
						
							var editor_content = tinyMCE.activeEditor.getContent({format : 'raw'});
							var short_code = "sn-simple-email";
							
							if(editor_content.match(short_code) == null){
								window.tinyMCE.execInstanceCommand('content', 'mceInsertContent', false, '[sn-simple-email]');
							} else {
								alert("There is already a simple email in this post/page.");
							}
                        });

                        // Register snSimpleEmail button
                        ed.addButton('snSimpleEmail', {
                                title : 'Insert Simple Form',
                                cmd : 'mcesnSimpleEmail',
                                image : url + '/se-icon.gif'
                        });

                        // Add a node change handler, selects the button in the UI when a image is selected
                        ed.onNodeChange.add(function(ed, cm, n) {
                                cm.setActive('snSimpleEmail', n.nodeName == 'IMG');
                        });
                },

                /**
                 * Creates control instances based in the incomming name. This method is normally not
                 * needed since the addButton method of the tinymce.Editor class is a more easy way of adding buttons
                 * but you sometimes need to create more complex controls like listboxes, split buttons etc then this
                 * method can be used to create those.
                 *
                 * @param {String} n Name of the control to create.
                 * @param {tinymce.ControlManager} cm Control manager to use inorder to create new control.
                 * @return {tinymce.ui.Control} New control instance or null if no control was created.
                 */
                createControl : function(n, cm) {
                        return null;
                },

                /**
                 * Returns information about the plugin as a name/value array.
                 * The current keys are longname, author, authorurl, infourl and version.
                 *
                 * @return {Object} Name/value array containing information about the plugin.
                 */
                getInfo : function() {
                        return {
                                longname : 'snSimpleEmail plugin',
                                author : 'Some author',
                                authorurl : 'http://tinymce.moxiecode.com',
                                infourl : 'http://wiki.moxiecode.com/index.php/TinyMCE:Plugins/snSimpleEmail',
                                version : "1.0"
                        };
                }
        });

        // Register plugin
        tinymce.PluginManager.add('snSimpleEmail', tinymce.plugins.snSimpleEmailPlugin);
})();