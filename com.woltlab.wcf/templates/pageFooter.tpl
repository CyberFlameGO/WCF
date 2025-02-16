<footer id="pageFooter" class="pageFooter">
	{if !$errorField|empty || !$errorType|empty}
		<!--
			DEBUG: FORM_VALIDATION_FAILED
			
			errorField: {if $errorField|empty}(empty){else}{$errorField|print_r:true}{/if}
			
			errorType: {if $errorType|empty}(empty){else}{$errorType|print_r:true}{/if}
		
		-->
		{if ENABLE_DEBUG_MODE}
			<script>
				console.debug('FORM_VALIDATION_FAILED');
				console.debug('errorField:', '{if $errorField|empty}(empty){else}{$errorField|json_encode}{/if}');
				console.debug('errorType:', '{if $errorType|empty}(empty){else}{$errorType|json_encode}{/if}');
			</script>
		{/if}
	{/if}
	
	{assign var=__boxesFooter value=$__wcf->getBoxHandler()->getBoxes('footer')}
	{if $__wcf->getStyleHandler()->showStyleChanger()}
		{assign var=__showStyleChanger value=true}
	{else}
		{assign var=__showStyleChanger value=false}
	{/if}

	{if $__boxesFooter|count || !$boxesFooter|empty || $__showStyleChanger || $__wcf->getStyleHandler()->showColorSchemeSelector()}
		<div class="boxesFooter">
			<div class="layoutBoundary{if $__showStyleChanger || $__wcf->getStyleHandler()->showColorSchemeSelector()} clearfix{/if}">
				{hascontent}
					<div class="styleChanger jsOnly">
						{content}
							{if $__showStyleChanger}
								<button type="button" class="jsButtonStyleChanger">{lang}wcf.style.changeStyle{/lang}</button>
							{/if}
							{if $__wcf->getStyleHandler()->showColorSchemeSelector()}
								<button type="button" class="page__colorScheme jsButtonStyleColorScheme jsTooltip" title="{lang}wcf.style.setColorScheme{/lang}">
									<span class="page__colorScheme--dark">
										{icon name='moon' type='solid'}
									</span>
									<span class="page__colorScheme--light">
										{icon name='sun' type='solid'}
									</span>
								</button>
							{/if}
						{/content}
					</div>
				{/hascontent}
				{hascontent}
					<div class="boxContainer">
						{content}
							{if !$boxesFooter|empty}
								{@$boxesFooter}
							{/if}

							{foreach from=$__boxesFooter item=box}
								{@$box->render()}
							{/foreach}
						{/content}
					</div>
				{/hascontent}
			</div>
		</div>
	{/if}
	
	{hascontent}
		<div id="pageFooterCopyright" class="pageFooterCopyright">
			<div class="layoutBoundary">
				{content}
					{event name='footerContents'}
					
					{if ENABLE_BENCHMARK}{include file='benchmark'}{/if}
					
					{include file='pageFooterCopyright'}
				
					{if MODULE_WCF_AD && $__disableAds|empty}
						{@$__wcf->getAdHandler()->getAds('com.woltlab.wcf.footer.bottom')}
					{/if}
				{/content}
			</div>
		</div>
	{/hascontent}
</footer>
