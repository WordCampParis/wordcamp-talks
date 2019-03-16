/* global wctJSvars, _, Backbone */
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

    wctBulkMailer.Models.LogEmail = Backbone.Model.extend( {
        defaults: {
            type: '',
            log_message: '',
            user_email: ''
        }
    } );

    wctBulkMailer.Collections.LogEmails = Backbone.Collection.extend( {
        model: wctBulkMailer.Models.LogEmail
    } );

    wctBulkMailer.Models.Applicant = Backbone.Model.extend( {
        defaults: {
            id: 0,
            display_name: '',
            user_email: ''
        }
    } );

    wctBulkMailer.Collections.Applicants = Backbone.Collection.extend( {
        model: wctBulkMailer.Models.Applicant,

        send: function( email, logs ) {
            var data = email.attributes || {}, first = _.first( this.models ), self = this;

            if ( ! first.get( 'id' ) ) {
                return false;
            }

            wp.ajax.post( 'wct_email_applicant', _.extend( data, first.attributes ) ).always( function( response ) {
                self.remove( first );
                logs.add( response );

                if ( ! self.length ) {
                    logs.add( { type: 'info', log_message: wctJSvars.strings.endedBulk } );
                }
            } );
        }
    } );

    wctBulkMailer.Views.feedbackEntry = wctBulkMailer.View.extend( {
        tagName: 'li',
        template : wp.template( 'wct-log-entries' )
    } );

    wctBulkMailer.Views.feedBacks = wctBulkMailer.View.extend( {
        tagName: 'ul',

        initialize: function() {
            this.collection.on( 'add', this.addLogEntry, this );
        },

        addLogEntry: function( entry ) {
            this.views.add( new wctBulkMailer.Views.feedbackEntry( { model: entry } ) );
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
            class: 'button button-primary', // jshint ignore:line
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
            this.options.logs.add( { type: 'info', log_message: wctJSvars.strings.startedBulk } );
            this.collection.send( this.options.email, this.options.logs );
        },

        nextEmail: function() {
            if ( ! this.collection.length ) {
                this.options.email.clear();
            } else {
                this.collection.send( this.options.email, this.options.logs );
            }
        }
    } );

    wctBulkMailer.Views.Main = wctBulkMailer.View.extend( {
        initialize: function() {
            var Applicants = new wctBulkMailer.Collections.Applicants(), email = new Backbone.Model(),
                Logs = new wctBulkMailer.Collections.LogEmails();

            this.views.add( '#wordcamp-talks-mailer', new wctBulkMailer.Views.ApplicantList( { collection: Applicants } ) );
            this.views.add( '#wordcamp-talks-mailer', new wctBulkMailer.Views.label( {
                attributes: {
                    for: 'email-subject', // jshint ignore:line
                    class: 'label' // jshint ignore:line
                },
                content: wctJSvars.strings.emailSubject
            } ) );
            this.views.add( '#wordcamp-talks-mailer', new wctBulkMailer.Views.Input( {
                collection: Applicants,
                attributes: {
                    id: 'email-subject',
                    type: 'text',
                    class: 'widefat code', // jshint ignore:line
                    name: 'subject'
                },
                email: email
            } ) );
            this.views.add( '#wordcamp-talks-mailer', new wctBulkMailer.Views.label( {
                attributes: {
                    for: 'email-body-reply-to', // jshint ignore:line
                    class: 'label' // jshint ignore:line
                },
                content: wctJSvars.strings.emailReplyTo
            } ) );
            this.views.add( '#wordcamp-talks-mailer', new wctBulkMailer.Views.Input( {
                collection: Applicants,
                attributes: {
                    id: 'email-body-reply-to',
                    type: 'text',
                    class: 'widefat code', // jshint ignore:line
                    name: 'reply_to'
                },
                email: email
            } ) );
            this.views.add( '#wordcamp-talks-mailer', new wctBulkMailer.Views.label( {
                attributes: {
                    for: 'email-body-message', // jshint ignore:line
                    class: 'label' // jshint ignore:line
                },
                content: wctJSvars.strings.emailMessage
            } ) );
            this.views.add( '#wordcamp-talks-mailer', new wctBulkMailer.Views.bodyMessage( {
                collection: Applicants,
                attributes: {
                    id: 'email-body-message',
                    cols: 50,
                    rows: 10,
                    class: 'widefat code', // jshint ignore:line
                    name: 'content'
                },
                email: email
            } ) );
            this.views.add( '#wordcamp-talks-mailer', new wctBulkMailer.Views.submitMessage( {
                collection: Applicants,
                content: wctJSvars.strings.emailSubmit,
                email: email,
                logs: Logs
            } ) );

            this.views.add( '#wordcamp-talks-mailer-log-entries', new wctBulkMailer.Views.feedBacks( {
                collection: Logs
            } ) );
        }
    } );

    new wctBulkMailer.Views.Main( { el:'#wordcamp-talks-mailer-wrapper' } ).render();
} )( window.wp || {}, jQuery );
