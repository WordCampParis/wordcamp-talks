const { registerPlugin } = wp.plugins;
const { PluginPostStatusInfo } = wp.editPost;
const { createElement } = wp.element;
const { __, _x, _n, _nx } = wp.i18n;
const { withSelect } = wp.data;
const { get, forEach } = lodash;
const { SelectControl } = wp.components;

function TalkStatusPanel( { postType, status } ) {
	let stati = get( postType, [ 'labels', 'stati' ], {} );
	let options = [];

	if ( stati ) {
		forEach( stati, function ( label, value ) {
			options.push( { label: label, value: value } );
		} );
	}

	return (
		<PluginPostStatusInfo
			className="wordcamp-talks-status-info"
		>
			<SelectControl
				label={ __( 'Talk status', 'wordcamp-talks' ) }
				value={ status }
				options={ options }
			/>
		</PluginPostStatusInfo>
	);
};

const TalkStatusInfo = withSelect( ( select ) => {
	const { getEditedPostAttribute } = select( 'core/editor' );
	const { getPostType } = select( 'core' );
	const postTypeName = getEditedPostAttribute( 'type' );

	return {
		postType: getPostType( postTypeName ),
		status: getEditedPostAttribute( 'talk_status' ),
	};
} )( TalkStatusPanel );

registerPlugin( 'wordcamp-talks-editor-sidebar', {
	render: TalkStatusInfo,
} );
