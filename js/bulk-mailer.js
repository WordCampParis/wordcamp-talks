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
        model: wctBulkMailer.Models.Applicant,

        send: function( email ) {
            var data = email.attributes || {}, first = _.first( this.models ), self = this;

            if ( ! first.get( 'id' ) ) {
                return false;
            }

            wp.ajax.post( 'wct_email_applicant', _.extend( data, first.attributes ) ).done( function() {
                self.remove( first );
            } ).fail( function( response ) {
                var error = _.first( response );

                console.warn( error.message );
            } );
        }
    } );

    wctBulkMailer.Views.applicantEntry = wctBulkMailer.View.extend( {
        tagName: 'li',
        template : wp.template( 'wct-applicants-list' ),

        initialize: function() {
            this.model.on( 'remove', this.removeView, this );
        },

        removeView: function() {
            this.views.view.remove();
        }
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

    wctBulkMailer.Views.Input = wctBulkMailer.View.extend( {
        tagName: 'input',

        events: {
            blur : 'setEmailAttribute'
        },

        initialize: function() {
            this.options.email.on( 'change:sending', this.updateInputState, this );
        },

        setEmailAttribute: function( event ) {
            this.options.email.set( this.attributes.name, $( event.currentTarget ).val() );
        },

        updateInputState: function( model, attribute ) {
            if ( attribute ) {
                $( this.el ).prop( 'readonly', true );
            } else {
                $( this.el ).prop( 'readonly', false );

                if ( ! model.get( this.attributes.name ) ) {
                    $( this.el ).val( '' );
                }
            }
        }
    } );

    wctBulkMailer.Views.bodyMessage = wctBulkMailer.Views.Input.extend( {
        tagName: 'textarea',

        events: {
            keyup : 'setEmailAttribute'
        }
    } );

    wctBulkMailer.Views.submitMessage = wctBulkMailer.View.extend( {
        tagName: 'button',
        attributes: {
            class: 'button button-primary',
            disabled: true
        },

        events: {
            click : 'sendEmails'
        },

        initialize: function() {
            this.options.email.on( 'change:content', this.updateButtonState, this );
            this.options.email.on( 'change:sending', this.updateButtonState, this );
            this.collection.on( 'remove', this.nextEmail, this );
        },

        render: function() {
            this.$el.html( this.options.content );
            return this;
        },

        updateButtonState: function( model, attribute ) {
            if ( attribute && true !== model.get( 'sending' ) ) {
                $( this.el ).prop( 'disabled', false );
            } else {
                $( this.el ).prop( 'disabled', true );
            }
        },

        sendEmails: function() {
            this.options.email.set( 'sending', true );
            this.collection.send( this.options.email );
        },

        nextEmail: function() {
            if ( ! this.collection.length ) {
                this.options.email.clear();
            } else {
                this.collection.send( this.options.email );
            }
        }
    } );

    wctBulkMailer.Views.Main = wctBulkMailer.View.extend( {
        initialize: function() {
            var Applicants = new wctBulkMailer.Collections.Applicants(), email = new Backbone.Model();

            this.views.add( new wctBulkMailer.Views.ApplicantList( { collection: Applicants } ) );
            this.views.add( new wctBulkMailer.Views.label( {
                attributes: {
                    for: 'email-subject',
                    class: 'label'
                },
                content: 'Subject of your email'
            } ) );
            this.views.add( new wctBulkMailer.Views.Input( {
                collection: Applicants,
                attributes: {
                    id: 'email-subject',
                    type: 'text',
                    class: 'widefat code',
                    name: 'subject'
                },
                email: email
            } ) );
            this.views.add( new wctBulkMailer.Views.label( {
                attributes: {
                    for: 'email-body-reply-to',
                    class: 'label'
                },
                content: 'The email address to receive replies to.'
            } ) );
            this.views.add( new wctBulkMailer.Views.Input( {
                collection: Applicants,
                attributes: {
                    id: 'email-body-reply-to',
                    type: 'text',
                    class: 'widefat code',
                    name: 'reply_to'
                },
                email: email
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
                    class: 'widefat code',
                    name: 'content'
                },
                email: email
            } ) );
            this.views.add( new wctBulkMailer.Views.submitMessage( {
                collection: Applicants,
                content: 'Send',
                email: email
            } ) );
        }
    } );

    new wctBulkMailer.Views.Main( { el:'#wordcamp-talks-mailer' } ).render();
} )( window.wp || {}, jQuery );
