const { registerPlugin } = wp.plugins;
const { PluginPostStatusInfo } = wp.editPost;
const { createElement } = wp.element;
const { __, _x, _n, _nx } = wp.i18n;

const TalkStatusInfo = () => (
	<PluginPostStatusInfo
		className="wordcamp-talks-status-info"
	>
		{ __( 'Talk status', 'wordcamp-talks' ) }
	</PluginPostStatusInfo>
);

registerPlugin( 'wordcamp-talks-editor-sidebar', {
	render: TalkStatusInfo,
} );
