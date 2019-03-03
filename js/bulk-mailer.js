/* global wp, wctJSvars, _, Backbone */
( function( wp, $ ) {

    if ( 'undefined' === typeof wctJSvars || ! wctJSvars.users ) {
		return;
    }

    var wctBulkMailer = {
        'Models' : {},
        'Collections' : {},
        'Views': {},
        'View': wp.Backbone.View.extend( {
            prepare: function() {
                if ( ! _.isUndefined( this.model ) && _.isFunction( this.model.toJSON ) ) {
                    return this.model.toJSON();
                } else {
                    return {};
                }
            }
        } )
    };

    wctBulkMailer.Models.Applicant = Backbone.Model.extend( {
        defaults: {
            id: 0,
            display_name: '',
            user_email: ''
        }
    } );

    wctBulkMailer.Collections.Applicants = Backbone.Collection.extend( {
        model: wctBulkMailer.Models.Applicant
    } );

    wctBulkMailer.Views.applicantEntry = wctBulkMailer.View.extend( {
        tagName: 'li',
        template : wp.template( 'wct-applicants-list' )
    } );

    wctBulkMailer.Views.ApplicantList = wctBulkMailer.View.extend( {
        tagName: 'ul',

        initialize: function() {
            var models = [];

            this.collection.on( 'reset', this.addListEntry, this );

            _.each( wctJSvars.users, function( users ) {
                models.push( new wctBulkMailer.Models.Applicant( {
                    id: users.ID,
                    display_name: users.display_name,
                    user_email: users.user_email
                } ) );
            } );

            this.collection.reset( models );
        },

        addListEntry: function( collection ) {
            _.each( collection.models, function( applicant ) {
                this.views.add( new wctBulkMailer.Views.applicantEntry( { model: applicant } ) );
            }, this );
        }
    } );

    wctBulkMailer.Views.label = wctBulkMailer.View.extend( {
        tagName: 'label',

        render: function() {
            this.$el.html( this.options.content );
            return this;
        }
    } );

    wctBulkMailer.Views.replyTo = wctBulkMailer.View.extend( {
        tagName: 'input'
    } );

    wctBulkMailer.Views.bodyMessage = wctBulkMailer.View.extend( {
        tagName: 'textarea'
    } );

    wctBulkMailer.Views.Main = wctBulkMailer.View.extend( {
        initialize: function() {
            var Applicants = new wctBulkMailer.Collections.Applicants(), models = [];

            this.views.add( new wctBulkMailer.Views.ApplicantList( { collection: Applicants } ) );
            this.views.add( new wctBulkMailer.Views.label( {
                attributes: {
                    for: 'email-body-reply-to',
                    class: 'label'
                },
                content: 'The email address to receive replies to.'
            } ) );
            this.views.add( new wctBulkMailer.Views.replyTo( {
                collection: Applicants,
                attributes: {
                    id: 'email-body-reply-to',
                    type: 'text',
                    class: 'widefat code'
                }
            } ) );
            this.views.add( new wctBulkMailer.Views.label( {
                attributes: {
                    for: 'email-body-message',
                    class: 'label'
                },
                content: 'Message'
            } ) );
            this.views.add( new wctBulkMailer.Views.bodyMessage( {
                collection: Applicants,
                attributes: {
                    id: 'email-body-message',
                    cols: 50,
                    rows: 10,
                    class: 'widefat code'
                }
            } ) );
        }
    } );

    new wctBulkMailer.Views.Main( { el:'#wordcamp-talks-mailer' } ).render();
} )( window.wp || {}, jQuery );
