<div class="toolbox divider nobr">
	<input id="pageFormSubmit" type="button" class="submit" value="<?= lang('ionize_button_save_page') ?>" />
</div>

<div class="toolbox divider nobr" id="tPageDeleteButton">
	<input id="pageDeleteButton" type="button" class="button no" value="<?= lang('ionize_button_delete') ?>" />
</div>

<div class="toolbox divider">
	<input type="button" class="toolbar-button" id="sidecolumnSwitcher" value="<?= lang('ionize_label_hide_options') ?>" />
</div>


<div class="toolbox divider" id="tPageAddContentElement">
	<input id="addContentElement" type="button" class="toolbar-button element" value="<?= lang('ionize_label_add_content_element') ?>" />
</div>

<div class="toolbox" id="tPageMediaButton">
	<input id="addMedia" type="button" class="fmButton toolbar-button pictures" value="<?= lang('ionize_label_attach_media') ?>"/>
</div>

<div class="toolbox divider" id="tPageAddArticle">
	<input id="addArticle" type="button" class="toolbar-button plus" value="<?= lang('ionize_label_add_article') ?>" />
</div>




<script type="text/javascript">

	/**
	 * Form save action
	 * see init.js for more information about this method
	 *
	 */
	MUI.setFormSubmit('pageForm', 'pageFormSubmit', 'page/save');


	/**
	 * Delete & Duplicate button buttons
	 *
	 */
	var id = $('id_page').value;

	if ( ! id )
	{
		$('tPageDeleteButton').hide();
		$('tPageAddContentElement').hide();
		$('tPageMediaButton').hide();
		$('tPageAddArticle').hide();
	}
	else
	{
		// Delete button
		$('pageDeleteButton').setProperty('rel', id);
		ION.initItemDeleteEvent($('pageDeleteButton'), 'page');

		// Add Content Element button
		$('addContentElement').addEvent('click', function(e)
		{
			MUI.dataWindow('contentElement', 'ionize_title_add_content_element', 'element/add_element', {width:500, height:300}, {'parent':'page', 'id_parent': id});
		});


		$('addMedia').addEvent('click', function(e)
		{
			var e = new Event(e).stop();
			mediaManager.initParent('page', $('id_page').value);
			mediaManager.toggleFileManager();
		});


		/**
		 * Article create button link
		 */
		$('addArticle').addEvent('click', function(e)
		{
			var e = new Event(e).stop();
			
			MUI.updateContent({
				'element': $('mainPanel'),
				'loadMethod': 'xhr',
				'url': admin_url + 'article/create/' + id,
				'title': Lang.get('ionize_title_create_article')
			});
		});
	}

	/**
	 * Options show / hide button
	 *
	 */
	MUI.initSideColumn();

	/**
	 * Save with CTRL+s
	 *
	 */
	MUI.addFormSaveEvent('pageFormSubmit');

</script>
