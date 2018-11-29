const { registerPlugin } = wp.plugins;
const { PluginPostStatusInfo } = wp.editPost;
const { createElement } = wp.element;
const { __, _x, _n, _nx } = wp.i18n;
const { withSelect, withDispatch } = wp.data;
const { get, forEach } = lodash;
const { SelectControl } = wp.components;
const { compose } = wp.compose;

function TalkStatusPanel( { onUpdateTalkStatus, postType, status = 'wct_pending' } ) {
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
				onChange={ ( talkStatus ) => onUpdateTalkStatus( talkStatus ) }
				options={ options }
			/>
		</PluginPostStatusInfo>
	);
};

const TalkStatusInfo = compose( [
	withSelect( ( select ) => {
		const { getEditedPostAttribute } = select( 'core/editor' );
		const { getPostType } = select( 'core' );
		const postTypeName = getEditedPostAttribute( 'type' );

		return {
			postType: getPostType( postTypeName ),
			status: getEditedPostAttribute( 'talk_status' ),
		};
	} ),
	withDispatch( ( dispatch ) => ( {
		onUpdateTalkStatus( talkStatus ) {
			dispatch( 'core/editor' ).editPost( { talk_status: talkStatus } );
		},
	} ) ),
] )( TalkStatusPanel );

registerPlugin( 'wordcamp-talks-editor-sidebar', {
	render: TalkStatusInfo,
} );
