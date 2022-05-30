import { ToastController } from '@ionic/angular';
import { LoadingController } from '@ionic/angular';
import { AlertController } from '@ionic/angular';

export class MyCommonServices {

    private headerTitle: string;
    
    constructor( 
        public toastCtrl: ToastController,
        public loadingCtrl: LoadingController,
        public alertCtrl: AlertController
    ) {}

    titleHeaderGetter() {
        return this.headerTitle;
    }

    titleHeaderSetter(newTitle: string) {
        this.headerTitle = newTitle;
    }

    async presentLoadingDefault() {
        const loading = await this.loadingCtrl.create({
            message: 'Just a sec...'
        });
        await loading.present();
    
        const { role, data } = await loading.onDidDismiss();
    
        console.log('Loading dismissed!');
    }

    async presentLoading(cont: string, css?: string) {
        const classNi = (css) ? css : '';

        const loading = await this.loadingCtrl.create({
            message: '' + cont,
            duration: 2000,
            cssClass: classNi
        });
        await loading.present();
    
        const { role, data } = await loading.onDidDismiss();
    }

    dismissLoading(loader: any) {
        return false;
    }

    async presentToast(msg: string, type: string, durationMs?: number) {
        var toastType = '';
        if (type == 'success') {
            toastType = 'success';
        } else if (type == 'error') {
            toastType = 'error';
        } else if (type == 'api_err') {
            toastType = 'error';
            msg = (msg != '') ? msg : 'Oops! Something went wrong. Please try again.';
        } else {
            toastType = type;
        }

        const toast = await this.toastCtrl.create({
            message: '' + msg,
            duration: (durationMs) ? durationMs : 4000,
            position: 'top',
            cssClass: toastType
        });
        toast.present();
    }

    async presentLogsError(message: any) {
        const alert = await this.alertCtrl.create({
            header: 'Invalid time logs',
            subHeader: 'Please review the following:',
            message: message,
            buttons: [
                {
                    text: 'Got it!',
                    role: 'cancel'
                }
            ]
          });
      
          await alert.present();
    }

    async presentOnebuttonAlert(title: any, msg: any, subtitle?: any) {        
        const alert = await this.alertCtrl.create({
            header: title,
            subHeader: (subtitle ? subtitle : ''),
            message: msg,
            buttons: [
                {
                    text: 'Got it!',
                    role: 'cancel'
                }
            ]
          });
      
          await alert.present();
    }

    async showLoading(loadingId: string, loadingMessage: string = 'Loading...') {
        const loading = await this.loadingCtrl.create({
            id: loadingId,
            message: loadingMessage
        });
        return await loading.present();
    }

    async dismissLoader(loadingId: string) {
        return await this.loadingCtrl.dismiss(null, null, loadingId).then(() => console.log('loading dismissed'));
    }
}