PORTNAME=	phpvirtualbox
DISTVERSION=	5.0-5
PORTREVISION=	1
CATEGORIES=	www
MASTER_SITES=	SF/${PORTNAME}

MAINTAINER=	zoon1@nas4free.org
COMMENT=	Web Interface for VirtualBox

LICENSE=	GPLv3

NO_BUILD=	yes
# NAS4Free
#USE_PHP=	json session soap simplexml xml
USES=		dos2unix zip

DOS2UNIX_REGEX=	.*\.(php|txt|js|css|html)
ETCDIR=		${WWWDIR}

do-install:
	${MKDIR} ${STAGEDIR}${WWWDIR}
	(cd ${WRKSRC}/ && ${COPYTREE_SHARE} "*" ${STAGEDIR}${WWWDIR} \
	    "-not -name *.orig -not -name config.php-example")
	${INSTALL_DATA} ${WRKSRC}/config.php-example \
	    ${STAGEDIR}${WWWDIR}/config.php.sample

# NAS4Free install
	@echo "install for ${NAS4FREE_ROOTFS}"
	@${RM} -f ${STAGEDIR}${WWWDIR}/*.txt
	#@${MKDIR} -p ${NAS4FREE_ROOTFS}${WWWDIR}
	@${CP} -Rpv ${STAGEDIR}${WWWDIR} ${NAS4FREE_ROOTFS}${PREFIX}/www
# NAS4Free workaround incompatible version message
	@${MV} ${NAS4FREE_ROOTFS}${PREFIX}/www/phpvirtualbox/endpoints/lib/vboxwebService-5.0.wsdl ${NAS4FREE_ROOTFS}${PREFIX}/www/phpvirtualbox/endpoints/lib/vboxwebService-5.1.wsdl
	@${MV} ${NAS4FREE_ROOTFS}${PREFIX}/www/phpvirtualbox/endpoints/lib/vboxweb-5.0.wsdl ${NAS4FREE_ROOTFS}${PREFIX}/www/phpvirtualbox/endpoints/lib/vboxweb-5.1.wsdl
.include <bsd.port.mk>
