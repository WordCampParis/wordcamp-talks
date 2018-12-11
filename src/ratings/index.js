const { registerPlugin } = wp.plugins;
const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editPost;
const { createElement, Fragment } = wp.element;
const { PanelBody, PanelRow } = wp.components;
const { __, _x, _n, _nx } = wp.i18n;

const Ratings = () => (
	<Fragment>
		<PluginSidebarMoreMenuItem
			target="ratings-sidebar"
		>
			{ __( 'Ratings', 'wordcamp-talks' ) }
		</PluginSidebarMoreMenuItem>
		<PluginSidebar
			name="ratings-sidebar"
			title={ __( 'Ratings', 'wordcamp-talks' ) }
		>
			<PanelBody title={ __( 'Ratings', 'wordcamp-talks' ) }>
                <PanelRow>
                    <p>{ __( 'List of votes', 'wordcamp-talks' ) }</p>
                </PanelRow>
            </PanelBody>
		</PluginSidebar>
	</Fragment>
);

registerPlugin( 'wordcamp-talks-ratings-sidebar', {
	icon: 'star-empty',
	render: Ratings,
} );
