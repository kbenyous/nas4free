PORTNAME=		pam_mkhomedir
PORTVERSION=	1.0
PORTREVISION=	1
CATEGORIES=		security
COMMENT=		Create HOME with a PAM module on demand
MAINTAINER=		zoon1@nas4free.org

# No files are needed to be fetched.
DISTFILES?=
# No build is required for this port.
NO_BUILD?=		 yes
# Don't register a port installation as a package.
NO_PKG_REGISTER?=	 yes
# Set default target to execute when a port is calling a dependency.
#ALWAYS_BUILD_DEPENDS= yes
DEPENDS_TARGET=	 clean build

BUILD_DEPENDS=	${NONEXISTENT}:security/pam_mkhomedir

do-install:
	@${INSTALL_PROGRAM} -v ${PORTSDIR}/security/pam_mkhomedir/work/pam_mkhomedir-*/pam_mkhomedir.so ${NAS4FREE_ROOTFS}/usr/local/lib

.include <bsd.port.mk>
